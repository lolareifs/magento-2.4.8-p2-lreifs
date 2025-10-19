<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api\Data;

/**
 * Quote Extension Interface
 * Represents an immutable quote extension
 */
interface QuoteExtensionInterface
{
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const IS_IMMUTABLE = 'is_immutable';
    public const IS_EXPIRED = 'is_expired';
    public const TERMS_ACCEPTED = 'terms_accepted';
    public const CUSTOMER_REFERENCE = 'customer_reference';
    public const CUSTOM_FEE = 'custom_fee';
    public const SORTING_ORDER = 'sorting_order';
    public const METADATA = 'metadata';
    public const IMMUTABLE_CREATED_BY = 'immutable_created_by';
    public const IMMUTABLE_CREATED_AT = 'immutable_created_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return QuoteExtensionInterface
     */
    public function setEntityId($entityId);

    /**
     * Get quote ID
     *
     * @return int
     */
    public function getQuoteId(): ?int;

    /**
     * Set quote ID
     *
     * @param int $quoteId
     * @return QuoteExtensionInterface
     */
    public function setQuoteId(int $quoteId): QuoteExtensionInterface;

    /**
     * Is immutable
     *
     * @return bool
     */
    public function isImmutable(): bool;

    /**
     * Set immutable
     *
     * @param bool $isImmutable
     * @return QuoteExtensionInterface
     */
    public function setIsImmutable(bool $isImmutable): QuoteExtensionInterface;

    /**
     * Is expired
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Set expired
     *
     * @param bool $isExpired
     * @return QuoteExtensionInterface
     */
    public function setIsExpired(bool $isExpired): QuoteExtensionInterface;

    /**
     * Are terms accepted
     *
     * @return bool
     */
    public function getTermsAccepted(): bool;

    /**
     * Set terms accepted
     *
     * @param bool $termsAccepted
     * @return QuoteExtensionInterface
     */
    public function setTermsAccepted(bool $termsAccepted): QuoteExtensionInterface;

    /**
     * Get customer reference
     *
     * @return string|null
     */
    public function getCustomerReference(): ?string;

    /**
     * Set customer reference
     *
     * @param string|null $customerReference
     * @return QuoteExtensionInterface
     */
    public function setCustomerReference(?string $customerReference): QuoteExtensionInterface;

    /**
     * Get custom fee
     *
     * @return float
     */
    public function getCustomFee(): float;

    /**
     * Set custom fee
     *
     * @param float $customFee
     * @return QuoteExtensionInterface
     */
    public function setCustomFee(float $customFee): QuoteExtensionInterface;

    /**
     * Get sorting order
     *
     * @return int
     */
    public function getSortingOrder(): int;

    /**
     * Set sorting order
     *
     * @param int $sortingOrder
     * @return QuoteExtensionInterface
     */
    public function setSortingOrder(int $sortingOrder): QuoteExtensionInterface;

    /**
     * Get metadata
     *
     * @return mixed
     */
    public function getMetadata(): array;

    /**
     * Set metadata
     *
     * @param mixed $metadata
     * @return QuoteExtensionInterface
     */
    public function setMetadata(array $metadata): QuoteExtensionInterface;

    /**
     * Get immutable created by
     *
     * @return int|null
     */
    public function getImmutableCreatedBy(): ?int;

    /**
     * Set immutable created by
     *
     * @param int|null $adminUserId
     * @return QuoteExtensionInterface
     */
    public function setImmutableCreatedBy(?int $adminUserId): QuoteExtensionInterface;

    /**
     * Get immutable created at
     *
     * @return string|null
     */
    public function getImmutableCreatedAt(): ?string;

    /**
     * Set immutable created at
     *
     * @param string|null $createdAt
     * @return QuoteExtensionInterface
     */
    public function setImmutableCreatedAt(?string $createdAt): QuoteExtensionInterface;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return QuoteExtensionInterface
     */
    public function setCreatedAt(string $createdAt): QuoteExtensionInterface;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return QuoteExtensionInterface
     */
    public function setUpdatedAt(string $updatedAt): QuoteExtensionInterface;
}