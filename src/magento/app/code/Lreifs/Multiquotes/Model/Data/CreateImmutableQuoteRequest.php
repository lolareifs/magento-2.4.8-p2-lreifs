<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\Data;

use Lreifs\Multiquotes\Api\Data\CreateImmutableQuoteRequestInterface;
use Magento\Framework\DataObject;

/**
 * Create Immutable Quote Request DTO
 * 
 * Data Transfer Object for creating immutable quotes
 */
class CreateImmutableQuoteRequest extends DataObject implements CreateImmutableQuoteRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getQuoteId(): int
    {
        return (int)$this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(?int $quoteId): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(int $customerId): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmail(?string $customerEmail): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(?string $name): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getNotes(): ?string
    {
        return $this->getData(self::NOTES);
    }

    /**
     * @inheritDoc
     */
    public function setNotes(?string $notes): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::NOTES, $notes);
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData(self::EXPIRES_AT);
    }

    /**
     * @inheritDoc
     */
    public function setExpiresAt(?string $expiresAt): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function getAllowItemModification(): bool
    {
        return (bool)$this->getData(self::ALLOW_ITEM_MODIFICATION);
    }

    /**
     * @inheritDoc
     */
    public function setAllowItemModification(bool $allowItemModification): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::ALLOW_ITEM_MODIFICATION, $allowItemModification);
    }

    /**
     * @inheritDoc
     */
    public function getAllowPriceModification(): bool
    {
        return (bool)$this->getData(self::ALLOW_PRICE_MODIFICATION);
    }

    /**
     * @inheritDoc
     */
    public function setAllowPriceModification(bool $allowPriceModification): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::ALLOW_PRICE_MODIFICATION, $allowPriceModification);
    }

    /**
     * @inheritDoc
     */
    public function getAllowQuantityModification(): bool
    {
        return (bool)$this->getData(self::ALLOW_QUANTITY_MODIFICATION);
    }

    /**
     * @inheritDoc
     */
    public function setAllowQuantityModification(bool $allowQuantityModification): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::ALLOW_QUANTITY_MODIFICATION, $allowQuantityModification);
    }

    /**
     * @inheritDoc
     */
    public function getBusinessRules(): array
    {
        $data = $this->getData(self::BUSINESS_RULES);
        return $data && is_array($data) ? $data : [];
    }

    /**
     * @inheritDoc
     */
    public function setBusinessRules(array $businessRules): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::BUSINESS_RULES, $businessRules);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        $data = $this->getData(self::METADATA);
        return $data && is_array($data) ? $data : [];
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(array $metadata): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::METADATA, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function getProtectionSettings(): array
    {
        $data = $this->getData(self::PROTECTION_SETTINGS);
        return $data && is_array($data) ? $data : [];
    }

    /**
     * @inheritDoc
     */
    public function setProtectionSettings(array $protectionSettings): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::PROTECTION_SETTINGS, $protectionSettings);
    }

    /**
     * @inheritDoc
     */
    public function getForceImmutable(): bool
    {
        return (bool)$this->getData(self::FORCE_IMMUTABLE);
    }

    /**
     * @inheritDoc
     */
    public function setForceImmutable(bool $forceImmutable): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::FORCE_IMMUTABLE, $forceImmutable);
    }

    /**
     * @inheritDoc
     */
    public function getAutoActivate(): bool
    {
        return (bool)$this->getData(self::AUTO_ACTIVATE);
    }

    /**
     * @inheritDoc
     */
    public function setAutoActivate(bool $autoActivate): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::AUTO_ACTIVATE, $autoActivate);
    }

    /**
     * Get all data as array
     *
     * @param array $keys
     * @return array
     */
    public function toArray(array $keys = []): array
    {
        $data = [
            self::QUOTE_ID => $this->getQuoteId(),
            self::CUSTOMER_ID => $this->getCustomerId(),
            self::NAME => $this->getName(),
            self::DESCRIPTION => $this->getDescription(),
            self::EXPIRES_AT => $this->getExpiresAt(),
            self::ALLOW_ITEM_MODIFICATION => $this->getAllowItemModification(),
            self::ALLOW_PRICE_MODIFICATION => $this->getAllowPriceModification(),
            self::ALLOW_QUANTITY_MODIFICATION => $this->getAllowQuantityModification(),
            self::BUSINESS_RULES => $this->getBusinessRules(),
            self::METADATA => $this->getMetadata(),
            self::PROTECTION_SETTINGS => $this->getProtectionSettings(),
            self::FORCE_IMMUTABLE => $this->getForceImmutable(),
            self::AUTO_ACTIVATE => $this->getAutoActivate(),
            self::CUSTOMER_REFERENCE => $this->getCustomerReference(),
            self::CUSTOM_FEE => $this->getCustomFee(),
            self::ADMIN_USER_ID => $this->getAdminUserId(),
            self::IP_ADDRESS => $this->getIpAddress(),
            self::USER_AGENT => $this->getUserAgent(),
        ];

        // If specific keys are requested, filter the data
        if (!empty($keys)) {
            return array_intersect_key($data, array_flip($keys));
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerReference(): ?string
    {
        return $this->getData(self::CUSTOMER_REFERENCE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerReference(?string $customerReference): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::CUSTOMER_REFERENCE, $customerReference);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFee(): ?float
    {
        return $this->getData(self::CUSTOM_FEE) ? (float)$this->getData(self::CUSTOM_FEE) : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomFee(?float $customFee): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::CUSTOM_FEE, $customFee);
    }

    /**
     * @inheritDoc
     */
    public function getAdminUserId(): ?int
    {
        return $this->getData(self::ADMIN_USER_ID) ? (int)$this->getData(self::ADMIN_USER_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setAdminUserId(?int $adminUserId): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritDoc
     */
    public function getIpAddress(): ?string
    {
        return $this->getData(self::IP_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setIpAddress(?string $ipAddress): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::IP_ADDRESS, $ipAddress);
    }

    /**
     * @inheritDoc
     */
    public function getUserAgent(): ?string
    {
        return $this->getData(self::USER_AGENT);
    }

    /**
     * @inheritDoc
     */
    public function setUserAgent(?string $userAgent): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::USER_AGENT, $userAgent);
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->getData(self::ITEMS);
    }

    /**
     * @inheritDoc
     */
    public function setItems($items): CreateImmutableQuoteRequestInterface
    {
        return $this->setData(self::ITEMS, $items);
    }
}