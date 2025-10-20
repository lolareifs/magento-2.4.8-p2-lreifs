<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension as QuoteExtensionResource;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\Collection;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;
use Lreifs\Multiquotes\Model\QuoteExtensionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Quote Extension Repository with Database persistence
 */
class QuoteExtensionRepository implements QuoteExtensionRepositoryInterface
{
    /**
     * @var QuoteExtensionResource
     */
    private $resource;

    /**
     * @var QuoteExtensionFactory
     */
    private $quoteExtensionFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * Constructor
     */
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Magento\Framework\App\CacheInterface */
    private $cache;

    public function __construct(
        QuoteExtensionResource $resource,
        QuoteExtensionFactory $quoteExtensionFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->resource = $resource;
        $this->quoteExtensionFactory = $quoteExtensionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function save(QuoteExtensionInterface $quoteExtension): QuoteExtensionInterface
    {
        try {
            $this->resource->save($quoteExtension);
            $this->eventManager->dispatch('multiquotes_quoteextension_save_after', ['quote_extension' => $quoteExtension]);
            $this->logger->info('QuoteExtension saved', [
                'entity_id' => $quoteExtension->getEntityId(),
                'quote_id' => $quoteExtension->getQuoteId(),
                'user' => $this->getCurrentUser(),
                'ip' => $this->getClientIp(),
                'timestamp' => date('c'),
            ]);
            // ...existing code...
        } catch (\Exception $exception) {
            $this->logger->error('Error saving QuoteExtension', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw new CouldNotSaveException(__('Could not save quote extension: %1', $exception->getMessage()), $exception);
        }
        return $quoteExtension;
    }

    /**
     * @inheritDoc
     */
    public function get(int $id): QuoteExtensionInterface
    {
        $quoteExtension = $this->quoteExtensionFactory->create();
        $this->resource->load($quoteExtension, $id);
        if (!$quoteExtension->getEntityId()) {
            throw new NoSuchEntityException(__('Quote extension with id "%1" does not exist.', $id));
        }
        return $quoteExtension;
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface
    {
        $quoteExtension = $this->quoteExtensionFactory->create();
        $this->resource->load($quoteExtension, $quoteId, 'quote_id');
        if (!$quoteExtension->getEntityId()) {
            throw new NoSuchEntityException(__('Quote extension for quote id "%1" does not exist.', $quoteId));
        }
        return $quoteExtension;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(QuoteExtensionInterface $quoteExtension): bool
    {
        try {
            $this->resource->delete($quoteExtension);
            $this->eventManager->dispatch('multiquotes_quoteextension_delete_after', ['quote_extension' => $quoteExtension]);
            $this->logger->info('QuoteExtension deleted', [
                'entity_id' => $quoteExtension->getEntityId(),
                'quote_id' => $quoteExtension->getQuoteId(),
                'user' => $this->getCurrentUser(),
                'ip' => $this->getClientIp(),
                'timestamp' => date('c'),
            ]);
            // Invalidate cache
            $this->cache->remove($this->getCacheKey($quoteExtension->getEntityId()));
        } catch (\Exception $exception) {
            $this->logger->error('Error deleting QuoteExtension', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw new CouldNotDeleteException(__('Could not delete quote extension: %1', $exception->getMessage()), $exception);
        }
        return true;
    }
    /**
     * Get cache key for QuoteExtension entity
     */
    private function getCacheKey($id): string
    {
        return 'multiquotes_quoteextension_' . $id;
    }
    /**
     * Get current user (admin/customer context)
     */
    private function getCurrentUser(): string
    {
        // Implement user context detection (admin/customer/session)
        return 'system';
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->get($id));
    }

    /**
     * @inheritDoc
     */
    public function getByImmutableHash(string $hash): QuoteExtensionInterface
    {
        $quoteExtension = $this->quoteExtensionFactory->create();
        $this->resource->load($quoteExtension, $hash, 'immutable_hash');
        if (!$quoteExtension->getEntityId()) {
            throw new NoSuchEntityException(__('Quote extension with hash "%1" does not exist.', $hash));
        }
        return $quoteExtension;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId(int $customerId): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getActiveByCustomerId(int $customerId): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
                   ->addFieldToFilter('status', 'active');
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getExpiredExtensions(): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('expiration_date', ['lt' => new \DateTime()]);
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getByType(string $extensionType): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('extension_type', $extensionType);
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getImmutableExtensions(): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_immutable', 1);
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function validateExtension(QuoteExtensionInterface $quoteExtension): bool
    {
        // Basic validation logic
        if (!$quoteExtension->getQuoteId() || !$quoteExtension->getCustomerId()) {
            return false;
        }
        
        // Check if not expired
        if ($quoteExtension->getExpirationDate() && 
            $quoteExtension->getExpirationDate() < new \DateTime()) {
            return false;
        }
        
        return true;
    }

    /**
     * @inheritDoc
     */
    public function softDelete(int $quoteExtensionId): bool
    {
        try {
            $quoteExtension = $this->get($quoteExtensionId);
            $quoteExtension->setStatus('deleted');
            $quoteExtension->setDeletedAt(new \DateTime());
            $this->save($quoteExtension);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function bulkUpdate(array $quoteExtensionIds, array $data): int
    {
        $updated = 0;
        foreach ($quoteExtensionIds as $id) {
            try {
                $quoteExtension = $this->get($id);
                foreach ($data as $key => $value) {
                    $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
                    if (method_exists($quoteExtension, $setter)) {
                        $quoteExtension->$setter($value);
                    }
                }
                $this->save($quoteExtension);
                $updated++;
            } catch (\Exception $e) {
                // Continue with next item
            }
        }
        return $updated;
    }

    /**
     * @inheritDoc
     */
    public function getImmutableQuotesByCustomer(int $customerId): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
                   ->addFieldToFilter('is_immutable', 1);
        
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getExpiredQuotes(\DateTime $beforeDate): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('expiration_date', ['lt' => $beforeDate->format('Y-m-d H:i:s')]);
        
        return $collection->getItems();
    }
}