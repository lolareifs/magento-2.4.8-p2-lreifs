<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api\Data;

/**
 * Quote Item Request Interface
 * DTO for individual items when creating quotes
 */
interface QuoteItemRequestInterface
{
    public const SKU = 'sku';
    public const QTY = 'qty';
    public const PRICE = 'price';
    public const PRODUCT_ID = 'product_id';
    public const CUSTOM_PRICE = 'custom_price';

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return QuoteItemRequestInterface
     */
    public function setSku(string $sku): QuoteItemRequestInterface;

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set quantity
     *
     * @param float $qty
     * @return QuoteItemRequestInterface
     */
    public function setQty(float $qty): QuoteItemRequestInterface;

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set price
     *
     * @param float $price
     * @return QuoteItemRequestInterface
     */
    public function setPrice(float $price): QuoteItemRequestInterface;

    /**
     * Get product ID (optional, resolved from SKU if not provided)
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set product ID
     *
     * @param int|null $productId
     * @return QuoteItemRequestInterface
     */
    public function setProductId(?int $productId): QuoteItemRequestInterface;

    /**
     * Get custom price flag
     *
     * @return bool
     */
    public function getCustomPrice(): bool;

    /**
     * Set custom price flag
     *
     * @param bool $customPrice
     * @return QuoteItemRequestInterface
     */
    public function setCustomPrice(bool $customPrice): QuoteItemRequestInterface;
}