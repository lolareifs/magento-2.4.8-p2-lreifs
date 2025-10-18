<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\Data;

use Lreifs\Multiquotes\Api\Data\QuoteItemRequestInterface;
use Magento\Framework\DataObject;

/**
 * Quote Item Request DTO
 * 
 * Data Transfer Object for quote items
 */
class QuoteItemRequest extends DataObject implements QuoteItemRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getSku(): string
    {
        return (string)$this->getData(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku(string $sku): QuoteItemRequestInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getQty(): float
    {
        return (float)$this->getData(self::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setQty(float $qty): QuoteItemRequestInterface
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setPrice(float $price): QuoteItemRequestInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function getProductId(): ?int
    {
        $productId = $this->getData(self::PRODUCT_ID);
        return $productId ? (int)$productId : null;
    }

    /**
     * @inheritDoc
     */
    public function setProductId(?int $productId): QuoteItemRequestInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomPrice(): bool
    {
        return (bool)$this->getData(self::CUSTOM_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomPrice(bool $customPrice): QuoteItemRequestInterface
    {
        return $this->setData(self::CUSTOM_PRICE, $customPrice);
    }
}