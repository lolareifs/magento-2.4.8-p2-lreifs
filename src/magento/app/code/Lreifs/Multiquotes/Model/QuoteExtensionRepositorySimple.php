<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Simple QuoteExtension Repository for initial setup
 */
class QuoteExtensionRepositorySimple implements QuoteExtensionRepositoryInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * Basic constructor
     */
    public function __construct()
    {
        // Simple constructor for initial setup
    }

    /**
     * @inheritDoc
     */
    public function save(QuoteExtensionInterface $quoteExtension): QuoteExtensionInterface
    {
        // Basic implementation - stores in memory cache
        $this->cache[$quoteExtension->getEntityId()] = $quoteExtension;
        return $quoteExtension;
    }

    /**
     * @inheritDoc
     */
    public function get(int $quoteExtensionId): QuoteExtensionInterface
    {
        if (!isset($this->cache[$quoteExtensionId])) {
            throw new NoSuchEntityException(__('Quote extension with id "%1" does not exist.', $quoteExtensionId));
        }
        return $this->cache[$quoteExtensionId];
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface
    {
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->getQuoteId() === $quoteId) {
                return $quoteExtension;
            }
        }
        throw new NoSuchEntityException(__('Quote extension for quote id "%1" does not exist.', $quoteId));
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        // Basic implementation - returns empty results
        $searchResults = new \Magento\Framework\Api\SearchResults();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems([]);
        $searchResults->setTotalCount(0);
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(QuoteExtensionInterface $quoteExtension): bool
    {
        unset($this->cache[$quoteExtension->getEntityId()]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $quoteExtensionId): bool
    {
        if (!isset($this->cache[$quoteExtensionId])) {
            throw new NoSuchEntityException(__('Quote extension with id "%1" does not exist.', $quoteExtensionId));
        }
        unset($this->cache[$quoteExtensionId]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getByImmutableHash(string $hash): QuoteExtensionInterface
    {
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->getImmutableHash() === $hash) {
                return $quoteExtension;
            }
        }
        throw new NoSuchEntityException(__('Quote extension with hash "%1" does not exist.', $hash));
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId(int $customerId): array
    {
        $results = [];
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->getCustomerId() === $customerId) {
                $results[] = $quoteExtension;
            }
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function getActiveByCustomerId(int $customerId): array
    {
        $results = [];
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->getCustomerId() === $customerId && $quoteExtension->getStatus() === 'active') {
                $results[] = $quoteExtension;
            }
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function getExpiredExtensions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getByType(string $extensionType): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getImmutableExtensions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function validateExtension(QuoteExtensionInterface $quoteExtension): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function softDelete(int $quoteExtensionId): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function bulkUpdate(array $quoteExtensionIds, array $data): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getImmutableQuotesByCustomer(int $customerId): array
    {
        $results = [];
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->getCustomerId() === $customerId && $quoteExtension->isImmutable()) {
                $results[] = $quoteExtension;
            }
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function getExpiredQuotes(\DateTime $beforeDate): array
    {
        $results = [];
        foreach ($this->cache as $quoteExtension) {
            if ($quoteExtension->isExpired() && $quoteExtension->getExpirationDate() < $beforeDate) {
                $results[] = $quoteExtension;
            }
        }
        return $results;
    }
}