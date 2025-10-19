<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Quote Extension Repository Interface
 * Provides CRUD operations for quote extensions
 */
interface QuoteExtensionRepositoryInterface
{
    /**
     * Get quote extension by ID
     *
     * @param int $id
     * @return QuoteExtensionInterface
     * @throws NoSuchEntityException
     */
    public function get(int $id): QuoteExtensionInterface;

    /**
     * Get quote extension by quote ID
     *
     * @param int $quoteId
     * @return QuoteExtensionInterface
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface;

    /**
     * Save quote extension
     *
     * @param QuoteExtensionInterface $quoteExtension
     * @return QuoteExtensionInterface
     * @throws LocalizedException
     */
    public function save(QuoteExtensionInterface $quoteExtension): QuoteExtensionInterface;

    /**
     * Get list of quote extensions
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete quote extension
     *
     * @param QuoteExtensionInterface $quoteExtension
     * @return bool
     * @throws LocalizedException
     */
    public function delete(QuoteExtensionInterface $quoteExtension): bool;

    /**
     * Delete quote extension by ID
     *
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $id): bool;

    /**
     * Get immutable quotes by customer ID
     *
     * @param int $customerId
     * @return QuoteExtensionInterface[]
     */
    public function getImmutableQuotesByCustomer(int $customerId): array;

    /**
     * Get expired quotes before date
     *
     * @param \DateTime $beforeDate
     * @return QuoteExtensionInterface[]
     */
    public function getExpiredQuotes(\DateTime $beforeDate): array;
}