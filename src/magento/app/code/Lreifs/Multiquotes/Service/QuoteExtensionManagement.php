<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Service;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Lreifs\Multiquotes\Api\Data\CreateImmutableQuoteRequestInterface;
use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Model\QuoteExtensionFactory;
use Lreifs\Multiquotes\Service\Guard\ImmutableQuoteGuard;
use Lreifs\Multiquotes\Service\Audit\AuditLogger;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * Quote Extension Management Service
 * Contains all business logic for immutable quotes
 */
class QuoteExtensionManagement implements QuoteExtensionManagementInterface
{
    public function __construct(
        private readonly QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly QuoteExtensionFactory $quoteExtensionFactory,
        private readonly ImmutableQuoteGuard $guard,
        private readonly AuditLogger $auditLogger,
        private readonly EventManagerInterface $eventManager,
        private readonly QuoteManagement $quoteManagement,
        private readonly CartManagementInterface $cartManagement,
        private readonly CartItemInterfaceFactory $cartItemFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly QuoteFactory $cartFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly QuoteCollectionFactory $quoteCollectionFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a new immutable quote with items (no existing quote required)
     */
    public function createImmutableQuoteWithItems(CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface
    {
        try {
            // Ensure we're creating a NEW quote - remove any quote_id that might have been passed
            $request->setQuoteId(null);
            
            // Resolve customer ID from request (by ID or email)
            $customerId = $this->resolveCustomerId($request);
            
            // Update request with resolved customer ID
            $request->setCustomerId($customerId);

            // Validate that items are provided
            if (empty($request->getItems())) {
                throw new LocalizedException(__('Items are required when creating a quote without quote_id'));
            }

            // Validate that all products in items exist
            $this->validateRequestItems($request->getItems());

            // Instead of using createEmptyCartForCustomer (which reuses existing active quotes)
            // Create a completely new quote directly to ensure incremental ID
            $this->logger->info('Creating NEW quote with incremental ID', [
                'customer_id' => $request->getCustomerId()
            ]);
            
            // Create quote directly via factory to ensure NEW incremental ID
            $cartModel = $this->cartFactory->create();
            $cartModel->setCustomerId($request->getCustomerId());
            $cartModel->setStoreId($this->storeManager->getStore()->getId());
            $cartModel->setIsActive($request->getAutoActivate() ?? false);
            
            // Save to get auto-increment ID
            $this->cartRepository->save($cartModel);

            // Add items to the quote
            $this->addItemsToQuote($cartModel, $request->getItems());

            // Save the quote with items
            $this->cartRepository->save($cartModel);

            // Now create the quote extension using the new quote ID
            $request->setQuoteId((int)$cartModel->getId());

            // Check if quote extension already exists (shouldn't, but be safe)
            try {
                $existing = $this->quoteExtensionRepository->getByQuoteId($request->getQuoteId());
                $quoteExtension = $existing;
            } catch (NoSuchEntityException $e) {
                // Create new extension
                $quoteExtension = $this->quoteExtensionFactory->create();
                $quoteExtension->setQuoteId($request->getQuoteId());
            }

            // Ensure customer_id is set (required for lookups)
            $quoteExtension->setCustomerId($request->getCustomerId());

            // Set immutable properties
            $quoteExtension->setIsImmutable(true);
            $quoteExtension->setImmutableCreatedBy($request->getAdminUserId());
            $quoteExtension->setImmutableCreatedAt(date('Y-m-d H:i:s'));
            
            // Set expiration date if provided
            if ($request->getExpiresAt() !== null) {
                $quoteExtension->setExpiresAt($request->getExpiresAt());
            }
            
            // Set optional properties
            if ($request->getCustomerReference() !== null) {
                $quoteExtension->setCustomerReference($request->getCustomerReference());
            }
            
            if ($request->getCustomFee() !== null) {
                $quoteExtension->setCustomFee($request->getCustomFee());
            }
            
            // Handle metadata safely
            $metadata = $request->getMetadata();
            if (!empty($metadata) && is_array($metadata)) {
                $quoteExtension->setMetadata($metadata);
            }

            // Save the extension
            $savedExtension = $this->quoteExtensionRepository->save($quoteExtension);

            // Log the creation
            $this->auditLogger->logQuoteCreation($savedExtension, [
                'admin_user_id' => $request->getAdminUserId(),
                'ip_address' => $request->getIpAddress(),
                'user_agent' => $request->getUserAgent(),
                'customer_id' => $request->getCustomerId(),
                'created_from_items' => true
            ]);

            // Dispatch creation event
            $this->eventManager->dispatch('lreifs_immutable_quote_created_with_items', [
                'quote_extension' => $savedExtension,
                'customer_id' => $request->getCustomerId(),
                'admin_user_id' => $request->getAdminUserId(),
                'items' => $request->getItems(),
                'creation_context' => [
                    'ip_address' => $request->getIpAddress(),
                    'user_agent' => $request->getUserAgent(),
                    'metadata' => $request->getMetadata()
                ]
            ]);

            $this->logger->info('Immutable quote created with items successfully', [
                'quote_id' => $request->getQuoteId(),
                'extension_id' => $savedExtension->getEntityId(),
                'customer_id' => $request->getCustomerId(),
                'items_count' => count($request->getItems()),
                'is_active' => $cartModel->getIsActive(),
                'auto_activate' => $request->getAutoActivate()
            ]);

            return $savedExtension;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create immutable quote with items', [
                'customer_id' => $request->getCustomerId(),
                'items' => $request->getItems(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new LocalizedException(
                __('Unable to create immutable quote with items: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Convert an existing quote to immutable
     */
    public function convertQuoteToImmutable(int $quoteId, CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface
    {
        try {
            // Load existing quote
            $quote = $this->cartRepository->get($quoteId);
            
            // Resolve customer ID and validate ownership
            $customerId = $this->resolveCustomerId($request);
            if ((int)$quote->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Quote #%1 does not belong to the specified customer', $quoteId));
            }
            
            // Force reload and ensure items are loaded
            $quote->collectTotals();
            
            // Force load items collection - this ensures items are properly loaded
            $quote->getAllItems();
            $quote->getAllVisibleItems();
            
            // Validate that the quote has items
            $this->validateQuoteProducts($quote);
            
            // Check if quote extension already exists
            try {
                $existing = $this->quoteExtensionRepository->getByQuoteId($quoteId);
                if ($existing->isImmutable()) {
                    throw new LocalizedException(__('Quote #%1 is already immutable', $quoteId));
                }
                $quoteExtension = $existing;
            } catch (NoSuchEntityException $e) {
                // Create new extension
                $quoteExtension = $this->quoteExtensionFactory->create();
                $quoteExtension->setQuoteId($quoteId);
            }
            
            // Set customer and immutable properties
            $quoteExtension->setCustomerId($customerId);
            $quoteExtension->setIsImmutable(true);
            $quoteExtension->setImmutableCreatedBy($request->getAdminUserId());
            $quoteExtension->setImmutableCreatedAt(date('Y-m-d H:i:s'));
            
            // Set expiration date if provided
            if ($request->getExpiresAt() !== null) {
                $quoteExtension->setExpiresAt($request->getExpiresAt());
            }
            
            // Set optional properties from request
            if ($request->getName() !== null) {
                $quoteExtension->setName($request->getName());
            }
            if ($request->getDescription() !== null) {
                $quoteExtension->setDescription($request->getDescription());
            }
            if ($request->getNotes() !== null) {
                $quoteExtension->setNotes($request->getNotes());
            }
            if ($request->getCustomerReference() !== null) {
                $quoteExtension->setCustomerReference($request->getCustomerReference());
            }
            if ($request->getCustomFee() !== null) {
                $quoteExtension->setCustomFee($request->getCustomFee());
            }
            if ($request->getMetadata() !== null) {
                $quoteExtension->setMetadata($request->getMetadata());
            }
            
            // Save the extension
            $savedExtension = $this->quoteExtensionRepository->save($quoteExtension);
            
            $this->logger->info('Existing quote converted to immutable successfully', [
                'quote_id' => $quoteId,
                'extension_id' => $savedExtension->getEntityId(),
                'customer_id' => $customerId
            ]);
            
            return $savedExtension;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to convert quote to immutable', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            
            throw new LocalizedException(
                __('Unable to convert quote to immutable: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Activate an immutable quote
     */
    public function activateQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        try {
            $extension = $this->quoteExtensionRepository->getByQuoteId($quoteId);
            
            if (!$extension->isImmutable()) {
                throw new LocalizedException(__('Quote is not immutable and cannot be activated'));
            }

            $customerId = $extension->getCustomerId();
            
            // First, deactivate all other quotes for this customer
            $customerQuotes = $this->quoteExtensionRepository->getImmutableQuotesByCustomer($customerId);
            $deactivatedQuotes = [];
            
            foreach ($customerQuotes as $customerQuote) {
                if ($customerQuote->getQuoteId() != $quoteId && $customerQuote->getIsActive()) {
                    $customerQuote->setIsActive(false);
                    $customerQuote->setStatus('inactive'); // Update status to inactive
                    $this->quoteExtensionRepository->save($customerQuote);
                    $deactivatedQuotes[] = $customerQuote->getQuoteId();
                    
                    // Log deactivation of other quotes
                    $this->auditLogger->logQuoteDeactivation($customerQuote, [
                        'admin_user_id' => $adminUserId,
                        'deactivated_at' => date('Y-m-d H:i:s'),
                        'reason' => 'New quote activated',
                        'new_active_quote_id' => $quoteId,
                        'auto_deactivated' => true
                    ]);
                }
            }
            
            // Now activate the target quote
            $extension->setIsActive(true);
            $extension->setStatus('active'); // Update status to active
            $this->quoteExtensionRepository->save($extension);
            
            // Set this quote as the customer's active cart in Magento
            $this->setCustomerActiveCart($customerId, $quoteId);
            
            // Log the activation
            $this->auditLogger->logQuoteActivation($extension, [
                'admin_user_id' => $adminUserId,
                'activated_at' => date('Y-m-d H:i:s'),
                'deactivated_quotes' => $deactivatedQuotes,
                'is_primary_active' => true
            ]);

            // Dispatch activation event
            $this->eventManager->dispatch('lreifs_immutable_quote_activated', [
                'quote_extension' => $extension,
                'admin_user_id' => $adminUserId,
                'timestamp' => new \DateTime(),
                'deactivated_quotes' => $deactivatedQuotes
            ]);

            $this->logger->info('Immutable quote activated as customer primary cart', [
                'quote_id' => $quoteId,
                'extension_id' => $extension->getEntityId(),
                'customer_id' => $customerId,
                'admin_user_id' => $adminUserId,
                'deactivated_quotes' => $deactivatedQuotes
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to activate quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Unable to activate quote: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Set customer's active cart in Magento
     *
     * @param int $customerId
     * @param int $quoteId
     * @throws LocalizedException
     */
    private function setCustomerActiveCart(int $customerId, int $quoteId): void
    {
        try {
            // Get the quote to activate
            $quote = $this->cartRepository->get($quoteId);
            
            // Ensure the quote belongs to the customer
            if ((int)$quote->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Quote does not belong to customer'));
            }
            
            // If customer has an existing active quote, deactivate it
            $existingQuote = null;
            try {
                $existingQuote = $this->cartRepository->getActiveForCustomer($customerId);
                if ($existingQuote && $existingQuote->getId() != $quoteId) {
                    $existingQuote->setIsActive(false);
                    $this->cartRepository->save($existingQuote);
                }
            } catch (\Exception $e) {
                // No existing active quote, continue
            }
            
            // Activate the new quote
            $quote->setIsActive(true);
            $quote->setUpdatedAt(date('Y-m-d H:i:s'));
            $this->cartRepository->save($quote);
            
            $this->logger->info('Customer active cart updated', [
                'customer_id' => $customerId,
                'new_active_quote_id' => $quoteId,
                'previous_active_quote_id' => $existingQuote ? $existingQuote->getId() : null
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to set customer active cart', [
                'customer_id' => $customerId,
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            
            throw new LocalizedException(
                __('Failed to set active cart: %1', $e->getMessage())
            );
        }
    }

    /**
     * Deactivate an immutable quote
     */
    public function deactivateQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        try {
            $extension = $this->quoteExtensionRepository->getByQuoteId($quoteId);
            
            if (!$extension->isImmutable()) {
                throw new LocalizedException(__('Quote is not immutable'));
            }

            // Deactivate the quote extension
            $extension->setIsActive(false);
            $extension->setStatus('inactive'); // Update status to inactive
            $this->quoteExtensionRepository->save($extension);
            
            // Also deactivate the underlying Magento quote
            $quote = $this->cartRepository->get($quoteId);
            $quote->setIsActive(false);
            $this->cartRepository->save($quote);

            // Log the deactivation
            $this->auditLogger->logQuoteDeactivation($extension, [
                'admin_user_id' => $adminUserId,
                'deactivated_at' => date('Y-m-d H:i:s'),
                'manual_deactivation' => true
            ]);

            // Dispatch deactivation event
            $this->eventManager->dispatch('lreifs_immutable_quote_deactivated', [
                'quote_extension' => $extension,
                'admin_user_id' => $adminUserId,
                'timestamp' => new \DateTime()
            ]);

            $this->logger->info('Immutable quote deactivated', [
                'quote_id' => $quoteId,
                'extension_id' => $extension->getEntityId(),
                'customer_id' => $extension->getCustomerId(),
                'admin_user_id' => $adminUserId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to deactivate quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Unable to deactivate quote: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Get customer's quotes
     */
    public function getCustomerQuotes(int $customerId, ?bool $immutableOnly = null): array
    {
        try {
            if ($immutableOnly === true) {
                return $this->quoteExtensionRepository->getImmutableQuotesByCustomer($customerId);
            }

            // Get all quotes for customer using direct repository method
            return $this->quoteExtensionRepository->getByCustomerId($customerId);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get customer quotes', [
                'customer_id' => $customerId,
                'immutable_only' => $immutableOnly,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Unable to retrieve customer quotes: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Convert immutable quote to order
     */
    public function convertToOrder(int $quoteId, int $customerId): int
    {
        try {
            $extension = $this->quoteExtensionRepository->getByQuoteId($quoteId);
            
            if (!$extension->isImmutable()) {
                throw new LocalizedException(__('Only immutable quotes can be converted to orders'));
            }

            $quote = $this->cartRepository->get($quoteId);
            
            if ((int)$quote->getCustomerId() !== $customerId) {
                throw new LocalizedException(__('Quote does not belong to the specified customer'));
            }

            // Convert quote to order using Magento's standard process
            $orderId = $this->quoteManagement->placeOrder($quoteId);

            // Log the conversion
            $this->auditLogger->logOrderConversion($extension, $orderId, [
                'customer_id' => $customerId,
                'converted_at' => date('Y-m-d H:i:s')
            ]);

            // Dispatch conversion event
            $this->eventManager->dispatch('lreifs_quote_converted_to_order', [
                'quote_extension' => $extension,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'timestamp' => new \DateTime()
            ]);

            $this->logger->info('Immutable quote converted to order', [
                'quote_id' => $quoteId,
                'order_id' => $orderId,
                'customer_id' => $customerId
            ]);

            return $orderId;

        } catch (\Exception $e) {
            $this->logger->error('Failed to convert quote to order', [
                'quote_id' => $quoteId,
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Unable to convert quote to order: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Check if quote can be modified
     */
    public function canModifyQuote(int $quoteId, string $action): bool
    {
        try {
            $extension = $this->quoteExtensionRepository->getByQuoteId($quoteId);
            return !$extension->isImmutable();
        } catch (NoSuchEntityException $e) {
            // If no extension exists, quote is mutable
            return true;
        }
    }

    /**
     * Get quote extension by quote ID
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface
    {
        return $this->quoteExtensionRepository->getByQuoteId($quoteId);
    }

    /**
     * Delete immutable quote
     */
    public function deleteQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        try {
            $extension = $this->quoteExtensionRepository->getByQuoteId($quoteId);

            // Log the deletion attempt
            $this->auditLogger->logQuoteDeletion($extension, [
                'admin_user_id' => $adminUserId,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

            // Delete the extension
            $result = $this->quoteExtensionRepository->delete($extension);

            // Dispatch deletion event
            $this->eventManager->dispatch('lreifs_immutable_quote_deleted', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'timestamp' => new \DateTime()
            ]);

            $this->logger->info('Immutable quote deleted', [
                'quote_id' => $quoteId,
                'extension_id' => $extension->getEntityId(),
                'admin_user_id' => $adminUserId
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Unable to delete quote: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Resolve and validate customer from request (by ID or email)
     *
     * @param CreateImmutableQuoteRequestInterface $request
     * @return int
     * @throws LocalizedException
     */
    private function resolveCustomerId(CreateImmutableQuoteRequestInterface $request): int
    {
        // If customer_id is provided, validate it exists
        if ($request->getCustomerId() > 0) {
            $this->validateCustomerExists($request->getCustomerId());
            return $request->getCustomerId();
        }
        
        // If customer_email is provided, find customer by email
        if ($request->getCustomerEmail()) {
            try {
                $customer = $this->customerRepository->get($request->getCustomerEmail());
                $this->logger->info('Customer resolved by email', [
                    'email' => $request->getCustomerEmail(),
                    'customer_id' => $customer->getId()
                ]);
                return (int)$customer->getId();
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(
                    __('Customer with email "%1" does not exist in Magento.', $request->getCustomerEmail())
                );
            }
        }
        
        throw new LocalizedException(
            __('Either customer_id or customer_email must be provided.')
        );
    }

    /**
     * Validate that customer exists in Magento
     *
     * @param int $customerId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateCustomerExists(int $customerId): void
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            if (!$customer->getId()) {
                throw new NoSuchEntityException(__('Customer with ID "%1" does not exist.', $customerId));
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Customer validation failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            throw new LocalizedException(
                __('Customer with ID "%1" does not exist in Magento.', $customerId),
                $e
            );
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during customer validation', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new LocalizedException(
                __('Unable to validate customer: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Validate that all products in the quote exist in Magento
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @throws LocalizedException
     */
    private function validateQuoteProducts(\Magento\Quote\Api\Data\CartInterface $quote): void
    {
        $invalidProducts = [];
        
        // Use getAllItems() instead of getItems() for better item loading
        $items = $quote->getAllItems();
        
        // Alternative: if getAllItems() is empty, try getAllVisibleItems()
        if (empty($items)) {
            $items = $quote->getAllVisibleItems();
        }
        
        // Debug: Log quote info
        $this->logger->info('Quote validation debug', [
            'quote_id' => $quote->getId(),
            'items_count_from_quote' => $quote->getItemsCount(),
            'items_count_from_getAllItems' => count($items ?? []),
            'items_qty' => $quote->getItemsQty(),
            'customer_id' => $quote->getCustomerId()
        ]);
        
        if (empty($items)) {
            throw new LocalizedException(__(
                'Quote #%1 has no items to validate. Please add items to the quote before creating an immutable quote.',
                $quote->getId()
            ));
        }

        foreach ($items as $item) {
            try {
                $productId = $item->getProductId();
                $sku = $item->getSku();
                
                if (!$productId || !$sku) {
                    $invalidProducts[] = [
                        'item_id' => $item->getItemId(),
                        'reason' => 'Missing product ID or SKU'
                    ];
                    continue;
                }

                // Validate product exists by ID
                $product = $this->productRepository->getById($productId);
                
                // Additional validation: check if product is still available/enabled
                if (!$product->getStatus() || $product->getStatus() != Status::STATUS_ENABLED) {
                    $invalidProducts[] = [
                        'product_id' => $productId,
                        'sku' => $sku,
                        'reason' => 'Product is disabled'
                    ];
                }
                
            } catch (NoSuchEntityException $e) {
                $invalidProducts[] = [
                    'product_id' => $item->getProductId(),
                    'sku' => $item->getSku(),
                    'reason' => 'Product does not exist'
                ];
            } catch (\Exception $e) {
                $invalidProducts[] = [
                    'product_id' => $item->getProductId(),
                    'sku' => $item->getSku(),
                    'reason' => 'Validation error: ' . $e->getMessage()
                ];
            }
        }

        if (!empty($invalidProducts)) {
            $this->logger->error('Product validation failed for quote', [
                'quote_id' => $quote->getId(),
                'invalid_products' => $invalidProducts
            ]);

            $errorMessages = [];
            foreach ($invalidProducts as $invalidProduct) {
                if (isset($invalidProduct['sku'])) {
                    $errorMessages[] = sprintf(
                        'Product SKU "%s" (ID: %s): %s',
                        $invalidProduct['sku'],
                        $invalidProduct['product_id'] ?? 'N/A',
                        $invalidProduct['reason']
                    );
                } else {
                    $errorMessages[] = sprintf(
                        'Quote item %s: %s',
                        $invalidProduct['item_id'] ?? 'N/A',
                        $invalidProduct['reason']
                    );
                }
            }

            throw new LocalizedException(
                __('Invalid products found in quote: %1', implode('; ', $errorMessages))
            );
        }

        $this->logger->info('All products in quote validated successfully', [
            'quote_id' => $quote->getId(),
            'products_count' => count($items)
        ]);
    }

    /**
     * Validate items array for quote creation
     *
     * @param array $items
     * @throws LocalizedException
     */
    private function validateRequestItems(array $items): void
    {
        $invalidItems = [];
        
        foreach ($items as $index => $item) {
            try {
                // Validate item structure
                if (!isset($item['sku']) || !isset($item['qty'])) {
                    $invalidItems[] = [
                        'index' => $index,
                        'reason' => 'Missing required fields (sku, qty)'
                    ];
                    continue;
                }

                // Validate product exists by SKU
                $product = $this->productRepository->get($item['sku']);
                
                // Validate product is enabled
                if (!$product->getStatus() || $product->getStatus() != Status::STATUS_ENABLED) {
                    $invalidItems[] = [
                        'sku' => $item['sku'],
                        'reason' => 'Product is disabled'
                    ];
                }

                // Validate quantity is positive
                if (!is_numeric($item['qty']) || $item['qty'] <= 0) {
                    $invalidItems[] = [
                        'sku' => $item['sku'],
                        'reason' => 'Invalid quantity: ' . ($item['qty'] ?? 'null')
                    ];
                }
                
            } catch (NoSuchEntityException $e) {
                $invalidItems[] = [
                    'sku' => $item['sku'] ?? 'unknown',
                    'reason' => 'Product does not exist'
                ];
            } catch (\Exception $e) {
                $invalidItems[] = [
                    'sku' => $item['sku'] ?? 'unknown',
                    'reason' => 'Validation error: ' . $e->getMessage()
                ];
            }
        }

        if (!empty($invalidItems)) {
            $this->logger->error('Item validation failed', [
                'invalid_items' => $invalidItems
            ]);

            $errorMessages = [];
            foreach ($invalidItems as $invalidItem) {
                if (isset($invalidItem['sku'])) {
                    $errorMessages[] = sprintf(
                        'Item SKU "%s": %s',
                        $invalidItem['sku'],
                        $invalidItem['reason']
                    );
                } else {
                    $errorMessages[] = sprintf(
                        'Item at index %s: %s',
                        $invalidItem['index'] ?? 'N/A',
                        $invalidItem['reason']
                    );
                }
            }

            throw new LocalizedException(
                __('Invalid items found: %1', implode('; ', $errorMessages))
            );
        }
    }

    /**
     * Add items to quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $items
     * @throws LocalizedException
     */
    private function addItemsToQuote(\Magento\Quote\Api\Data\CartInterface $quote, array $items): void
    {
        foreach ($items as $item) {
            try {
                $product = $this->productRepository->get($item['sku']);
                
                $cartItem = $this->cartItemFactory->create();
                $cartItem->setQuoteId($quote->getId());
                $cartItem->setProduct($product);
                $cartItem->setSku($item['sku']);
                $cartItem->setQty((float)$item['qty']);
                
                // Use provided price or product price
                $price = isset($item['price']) ? (float)$item['price'] : $product->getPrice();
                $cartItem->setPrice($price);

                $quote->addItem($cartItem);
                
            } catch (\Exception $e) {
                throw new LocalizedException(
                    __('Failed to add item %1 to quote: %2', $item['sku'], $e->getMessage())
                );
            }
        }
    }

    /**
     * Deactivate any existing active quotes for a customer
     * This ensures we create truly new quotes instead of reusing existing ones
     *
     * @param int $customerId
     * @return void
     */
    private function deactivateExistingActiveQuotes(int $customerId): void
    {
        try {
            // Get all active quotes for this customer
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId)
                ->addFilter('is_active', 1)
                ->create();

            $quotes = $this->cartRepository->getList($searchCriteria);

            // Deactivate each active quote
            foreach ($quotes->getItems() as $quote) {
                $quote->setIsActive(0);
                $this->cartRepository->save($quote);
                
                $this->logger->info('Deactivated existing quote for new creation', [
                    'quote_id' => $quote->getId(),
                    'customer_id' => $customerId
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the creation process
            $this->logger->warning('Failed to deactivate existing quotes', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get all quotes with optional filters (admin-only endpoint)
     * Includes both immutable and normal Magento core quotes
     *
     * @param int $pageSize
     * @param int $currentPage
     * @param array $filters Optional filters: ['is_immutable' => 1, 'customer_id' => 123, 'is_active' => 1, etc.]
     * @return array Array containing quotes and metadata
     */
    public function getAllImmutableQuotes(int $pageSize = 20, int $currentPage = 1, array $filters = []): array
    {
        try {
            // Get all core quotes
            $quoteCollection = $this->quoteCollectionFactory->create();
            
            // Apply pagination
            $quoteCollection->setPageSize($pageSize);
            $quoteCollection->setCurPage($currentPage);
            
            // Apply filters if provided
            if (!empty($filters)) {
                foreach ($filters as $field => $value) {
                    if ($field === 'is_immutable') {
                        // Special handling for immutable filter - need to join with quote_extension table
                        if ($value == 1) {
                            $quoteCollection->getSelect()->join(
                                ['qe' => $quoteCollection->getTable('lreifs_quote_extension')],
                                'main_table.entity_id = qe.quote_id',
                                []
                            )->where('qe.is_immutable = ?', 1);
                        } else {
                            // Show only non-immutable quotes (those without extension record or with is_immutable = 0)
                            $quoteCollection->getSelect()->joinLeft(
                                ['qe' => $quoteCollection->getTable('lreifs_quote_extension')],
                                'main_table.entity_id = qe.quote_id',
                                []
                            )->where('qe.is_immutable IS NULL OR qe.is_immutable = 0');
                        }
                    } elseif ($field === 'customer_id') {
                        $quoteCollection->addFieldToFilter('main_table.customer_id', $value);
                    } elseif ($field === 'is_active') {
                        $quoteCollection->addFieldToFilter('main_table.is_active', $value);
                    } elseif ($field === 'store_id') {
                        $quoteCollection->addFieldToFilter('main_table.store_id', $value);
                    } elseif ($field === 'created_at_from') {
                        $quoteCollection->addFieldToFilter('main_table.created_at', ['gteq' => $value]);
                    } elseif ($field === 'created_at_to') {
                        $quoteCollection->addFieldToFilter('main_table.created_at', ['lteq' => $value]);
                    }
                }
            }
            
            // Add ordering
            $quoteCollection->setOrder('entity_id', 'DESC');
            
            $totalCount = $quoteCollection->getSize();
            
            // Load the collection
            $quoteCollection->load();
            
            $quotes = [];
            foreach ($quoteCollection as $quote) {
                $quoteData = [
                    'quote_id' => $quote->getId(),
                    'customer_id' => $quote->getCustomerId(),
                    'customer_email' => $quote->getCustomerEmail(),
                    'is_active' => $quote->getIsActive(),
                    'store_id' => $quote->getStoreId(),
                    'created_at' => $quote->getCreatedAt(),
                    'updated_at' => $quote->getUpdatedAt(),
                    'reserved_order_id' => $quote->getReservedOrderId(),
                    'items_count' => $quote->getItemsCount(),
                    'items_qty' => $quote->getItemsQty(),
                    'grand_total' => $quote->getGrandTotal(),
                    'currency_code' => $quote->getQuoteCurrencyCode(),
                    'is_immutable' => false, // Default to false
                    'immutable_name' => null,
                    'immutable_description' => null,
                    'immutable_created_at' => null,
                ];
                
                // Check if this quote has an immutable extension
                try {
                    $quoteExtension = $this->quoteExtensionRepository->getByQuoteId((int)$quote->getId());
                    $quoteData['is_immutable'] = true;
                    $quoteData['immutable_name'] = $quoteExtension->getName();
                    $quoteData['immutable_description'] = $quoteExtension->getDescription();
                    $quoteData['immutable_created_at'] = $quoteExtension->getCreatedAt();
                } catch (NoSuchEntityException $e) {
                    // Quote is not immutable, keep default values
                }
                
                $quotes[] = $quoteData;
            }
            
            $result = [
                'quotes' => $quotes,
                'total_count' => $totalCount,
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => ceil($totalCount / $pageSize)
            ];
            
            $this->logger->info('Retrieved all quotes with filters', [
                'total_count' => $totalCount,
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'items_returned' => count($quotes),
                'filters_applied' => $filters
            ]);

            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all quotes', [
                'error' => $e->getMessage(),
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'filters' => $filters
            ]);
            throw new LocalizedException(__('Unable to retrieve quotes: %1', $e->getMessage()));
        }
    }
}