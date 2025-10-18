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
    public function __construct(
        QuoteExtensionResource $resource,
        QuoteExtensionFactory $quoteExtensionFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->quoteExtensionFactory = $quoteExtensionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(QuoteExtensionInterface $quoteExtension): QuoteExtensionInterface
    {
        try {
            $this->resource->save($quoteExtension);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
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
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
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