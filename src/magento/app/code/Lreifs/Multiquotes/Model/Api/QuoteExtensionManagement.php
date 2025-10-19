<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\Api;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Lreifs\Multiquotes\Api\Data\CreateImmutableQuoteRequestInterface;
use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;
use Lreifs\Multiquotes\Service\Audit\AuditLogger;
use Lreifs\Multiquotes\Service\Helper\ContextHelper;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * REST API Implementation for Quote Extension Management
 * Delegates to service layer for business logic
 */
class QuoteExtensionManagement implements QuoteExtensionManagementInterface
{
    public function __construct(
        private readonly \Lreifs\Multiquotes\Service\QuoteExtensionManagement $service,
        private readonly \Lreifs\Multiquotes\Model\Data\CreateImmutableQuoteRequestFactory $requestFactory,
        private readonly AuditLogger $auditLogger,
        private readonly ContextHelper $contextHelper,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Activate quote via REST API
     */
    public function activateQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        $startTime = microtime(true);
        
        try {
            $currentAdminUserId = $adminUserId ?? $this->contextHelper->getCurrentAdminUserId();

            // Log API access
            $this->auditLogger->logApiAccess(
                "/V1/lreifs-multiquotes/quotes/{$quoteId}/activate",
                'POST',
                array_merge($this->contextHelper->getAuditContext(), [
                    'quote_id' => $quoteId,
                    'admin_user_id' => $currentAdminUserId
                ])
            );

            // Delegate to service layer
            $result = $this->service->activateQuote($quoteId, $currentAdminUserId);

            // Log successful response
            $this->auditLogger->logApiAccess(
                "/V1/lreifs-multiquotes/quotes/{$quoteId}/activate",
                'POST',
                [
                    'quote_id' => $quoteId,
                    'admin_user_id' => $currentAdminUserId,
                    'response_status' => 'success',
                    'execution_time' => microtime(true) - $startTime
                ]
            );

            return $result;

        } catch (\Exception $e) {
            $this->auditLogger->logApiAccess(
                "/V1/lreifs-multiquotes/quotes/{$quoteId}/activate",
                'POST',
                [
                    'quote_id' => $quoteId,
                    'admin_user_id' => $currentAdminUserId ?? null,
                    'response_status' => 'error',
                    'error_message' => $e->getMessage(),
                    'execution_time' => microtime(true) - $startTime
                ]
            );

            throw $e;
        }
    }

    /**
     * Deactivate quote via REST API
     */
    public function deactivateQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        $currentAdminUserId = $adminUserId ?? $this->contextHelper->getCurrentAdminUserId();

        // Log API access  
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/quotes/{$quoteId}/deactivate",
            'POST',
            array_merge($this->contextHelper->getAuditContext(), [
                'quote_id' => $quoteId,
                'admin_user_id' => $currentAdminUserId
            ])
        );        // Delegate to service layer
        return $this->service->deactivateQuote($quoteId, $currentAdminUserId);
    }

    /**
     * Activate quote by extension ID via REST API
     */
    public function activateQuoteByExtensionId(int $extensionId, ?int $adminUserId = null): bool
    {
        $currentAdminUserId = $adminUserId ?? $this->contextHelper->getCurrentAdminUserId();

        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/extensions/{$extensionId}/activate",
            'POST',
            array_merge($this->contextHelper->getAuditContext(), [
                'extension_id' => $extensionId,
                'admin_user_id' => $currentAdminUserId
            ])
        );

        // Delegate to service layer
        return $this->service->activateQuoteByExtensionId($extensionId, $currentAdminUserId);
    }

    /**
     * Deactivate quote by extension ID via REST API
     */
    public function deactivateQuoteByExtensionId(int $extensionId, ?int $adminUserId = null): bool
    {
        $currentAdminUserId = $adminUserId ?? $this->contextHelper->getCurrentAdminUserId();

        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/extensions/{$extensionId}/deactivate",
            'POST',
            array_merge($this->contextHelper->getAuditContext(), [
                'extension_id' => $extensionId,
                'admin_user_id' => $currentAdminUserId
            ])
        );

        // Delegate to service layer
        return $this->service->deactivateQuoteByExtensionId($extensionId, $currentAdminUserId);
    }

    /**
     * Get customer quotes via REST API
     */
    public function getCustomerQuotes(int $customerId, ?bool $immutableOnly = null): array
    {
        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/customers/{$customerId}/lreifs-multiquotes",
            'GET',
            array_merge($this->contextHelper->getAuditContext(), [
                'customer_id' => $customerId,
                'immutable_only' => $immutableOnly
            ])
        );

        // Delegate to service layer
        return $this->service->getCustomerQuotes($customerId, $immutableOnly);
    }

    /**
     * Convert quote to order via REST API
     */
    public function convertToOrder(int $quoteId, int $customerId): int
    {
        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/quotes/{$quoteId}/convert-to-order",
            'POST',
            [
                'quote_id' => $quoteId,
                'customer_id' => $customerId,
                'ip_address' => $this->getClientIp()
            ]
        );

        // Delegate to service layer
        return $this->service->convertToOrder($quoteId, $customerId);
    }

    /**
     * Check if quote can be modified via REST API
     */
    public function canModifyQuote(int $quoteId, string $action): bool
    {
        // Delegate to service layer
        return $this->service->canModifyQuote($quoteId, $action);
    }

    /**
     * Get quote extension by quote ID via REST API
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface
    {
        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/quotes/{$quoteId}",
            'GET',
            array_merge($this->contextHelper->getAuditContext(), [
                'quote_id' => $quoteId
            ])
        );

        // Delegate to service layer
        return $this->service->getByQuoteId($quoteId);
    }

    /**
     * Delete quote via REST API
     */
    public function deleteQuote(int $quoteId, ?int $adminUserId = null): bool
    {
        $currentAdminUserId = $adminUserId ?? $this->contextHelper->getCurrentAdminUserId();

        // Log API access
        $this->auditLogger->logApiAccess(
            "/V1/lreifs-multiquotes/quotes/{$quoteId}",
            'DELETE',
            array_merge($this->contextHelper->getAuditContext(), [
                'quote_id' => $quoteId,
                'admin_user_id' => $currentAdminUserId
            ])
        );        // Delegate to service layer
        return $this->service->deleteQuote($quoteId, $currentAdminUserId);
    }

    /**
     * Create immutable quote with items via REST API
     *
     * @param CreateImmutableQuoteRequestInterface $request
     * @return QuoteExtensionInterface
     * @throws LocalizedException
     */
    public function createImmutableQuoteWithItems(CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface
    {
        $startTime = microtime(true);
        
        try {
            // Add API context to request
            $request->setIpAddress($this->contextHelper->getClientIp());
            $request->setUserAgent($this->contextHelper->getUserAgent());

            $this->logger->info('API createImmutableQuoteWithItems started', [
                'customer_id' => $request->getCustomerId(),
                'items_count' => count($request->getItems() ?? []),
                'ip_address' => $request->getIpAddress()
            ]);

            // Delegate to service layer
            $quoteExtension = $this->service->createImmutableQuoteWithItems($request);
            
            // Log audit event for API access
            $this->auditLogger->logApiAccess(
                'create-quote',
                'POST',
                [
                    'request_id' => $this->generateRequestId(),
                    'admin_user_id' => $this->contextHelper->getCurrentAdminUserId(),
                    'customer_id' => $request->getCustomerId(),
                    'quote_id' => $quoteExtension->getQuoteId(),
                    'items_count' => count($request->getItems() ?? []),
                    'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]
            );
            
            $this->logger->info('API createImmutableQuoteWithItems completed successfully', [
                'quote_extension_id' => $quoteExtension->getId(),
                'quote_id' => $quoteExtension->getQuoteId(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return $quoteExtension;
            
        } catch (\Exception $e) {
            $this->logger->error('API createImmutableQuoteWithItems failed', [
                'customer_id' => $request->getCustomerId(),
                'error' => $e->getMessage(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            throw $e;
        }
    }

    /**
     * Convert existing quote to immutable via REST API
     *
     * @param int $quoteId
     * @param CreateImmutableQuoteRequestInterface $request
     * @return QuoteExtensionInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function convertQuoteToImmutable(int $quoteId, CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface
    {
        $startTime = microtime(true);
        
        try {
            // Add API context to request
            $request->setIpAddress($this->contextHelper->getClientIp());
            $request->setRequestId($this->generateRequestId());
            
            // Get current admin user ID
            $currentAdminUserId = $this->contextHelper->getCurrentAdminUserId();
            if ($currentAdminUserId === null) {
                throw new LocalizedException(__('Admin user not found.'));
            }
            $request->setAdminUserId($currentAdminUserId);
            
            // Delegate to service layer
            $result = $this->service->convertQuoteToImmutable($quoteId, $request);
            
            // Log successful API access
            $this->auditLogger->logApiAccess(
                "/V1/lreifs-multiquotes/quotes/{$quoteId}/convert",
                'POST',
                [
                    'quote_id' => $quoteId,
                    'customer_id' => $request->getCustomerId(),
                    'admin_user_id' => $currentAdminUserId,
                    'ip_address' => $request->getIpAddress(),
                    'response_status' => 'success',
                    'result_id' => $result->getEntityId(),
                    'execution_time' => microtime(true) - $startTime
                ]
            );

            return $result;

        } catch (\Exception $e) {
            // Log API error
            $this->auditLogger->logApiAccess(
                "/V1/lreifs-multiquotes/quotes/{$quoteId}/convert",
                'POST',
                [
                    'quote_id' => $quoteId,
                    'customer_id' => $request->getCustomerId(),
                    'admin_user_id' => $request->getAdminUserId(),
                    'ip_address' => $request->getIpAddress(),
                    'response_status' => 'error',
                    'error_message' => $e->getMessage(),
                    'execution_time' => microtime(true) - $startTime
                ]
            );
            
            throw $e;
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
        $startTime = microtime(true);
        
        // Get filters from request parameters if not provided directly
        if (empty($filters)) {
            $requestParams = $this->request->getParams();
            $filters = [];
            
            // Map request parameters to filters
            foreach ($requestParams as $key => $value) {
                if (in_array($key, ['is_immutable', 'customer_id', 'is_active', 'store_id', 'created_at_from', 'created_at_to'])) {
                    $filters[$key] = $value;
                }
            }
        }
        
        $this->logger->info('API getAllQuotes started', [
            'page_size' => $pageSize,
            'current_page' => $currentPage,
            'filters' => $filters,
            'admin_user_id' => $this->contextHelper->getCurrentAdminUserId()
        ]);

        try {
            // Delegate to service layer
            $result = $this->service->getAllImmutableQuotes($pageSize, $currentPage, $filters);
            
            $this->logger->info('API getAllQuotes completed successfully', [
                'total_count' => $result['total_count'],
                'quotes_returned' => count($result['quotes']),
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'filters_applied' => $filters,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('API getAllQuotes failed', [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
}