<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * QuoteExtension Model
 * 
 * Represents a quote extension in the immutable quote system
 */
class QuoteExtension extends AbstractModel implements QuoteExtensionInterface
{
    // Import constants from interface
    public const ENTITY_ID = QuoteExtensionInterface::ENTITY_ID;
    public const QUOTE_ID = QuoteExtensionInterface::QUOTE_ID;
    public const IS_IMMUTABLE = QuoteExtensionInterface::IS_IMMUTABLE;
    public const IS_EXPIRED = QuoteExtensionInterface::IS_EXPIRED;
    public const TERMS_ACCEPTED = QuoteExtensionInterface::TERMS_ACCEPTED;
    public const CUSTOMER_REFERENCE = QuoteExtensionInterface::CUSTOMER_REFERENCE;
    public const CUSTOM_FEE = QuoteExtensionInterface::CUSTOM_FEE;
    public const SORTING_ORDER = QuoteExtensionInterface::SORTING_ORDER;
    public const METADATA = QuoteExtensionInterface::METADATA;
    public const IMMUTABLE_CREATED_BY = QuoteExtensionInterface::IMMUTABLE_CREATED_BY;
    public const IMMUTABLE_CREATED_AT = QuoteExtensionInterface::IMMUTABLE_CREATED_AT;
    public const CREATED_AT = QuoteExtensionInterface::CREATED_AT;
    public const UPDATED_AT = QuoteExtensionInterface::UPDATED_AT;
    
    // Additional constants for fields that exist in DB but not in interface
    public const CUSTOMER_ID = 'customer_id';
    public const EXTENSION_TYPE = 'extension_type';
    public const STATUS = 'status';
    public const IS_ACTIVE = 'is_active';
    public const LOCKED_AT = 'locked_at';
    public const LOCKED_BY_USER_ID = 'locked_by_user_id';
    public const IMMUTABLE_HASH = 'immutable_hash';
    public const BUSINESS_RULES = 'business_rules';
    public const PROTECTION_SETTINGS = 'protection_settings';
    public const ALLOW_ITEM_MODIFICATION = 'allow_item_modification';
    public const ALLOW_PRICE_MODIFICATION = 'allow_price_modification';
    public const ALLOW_QUANTITY_MODIFICATION = 'allow_quantity_modification';
    public const EXPIRES_AT = 'expires_at';
    public const EXPIRATION_NOTIFICATION_SENT = 'expiration_notification_sent';
    public const VERSION = 'version';
    public const PARENT_QUOTE_EXTENSION_ID = 'parent_quote_extension_id';
    public const CHANGE_LOG = 'change_log';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const NOTES = 'notes';
    public const CREATED_BY_USER_ID = 'created_by_user_id';
    public const UPDATED_BY_USER_ID = 'updated_by_user_id';
    public const CONVERTED_TO_ORDER_ID = 'converted_to_order_id';
    public const CONVERTED_AT = 'converted_at';

    /**
     * @var string
     */
    protected $_eventPrefix = 'lreifs_quote_extension';

    /**
     * @var string
     */
    protected $_eventObject = 'quote_extension';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): ?int
    {
        return $this->getData(self::QUOTE_ID) ? (int)$this->getData(self::QUOTE_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(int $quoteId): QuoteExtensionInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ? (int)$this->getData(self::CUSTOMER_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(?int $customerId): QuoteExtensionInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionType(): string
    {
        return $this->getData(self::EXTENSION_TYPE) ?? 'immutable';
    }

    /**
     * @inheritDoc
     */
    public function setExtensionType(string $extensionType): QuoteExtensionInterface
    {
        return $this->setData(self::EXTENSION_TYPE, $extensionType);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return $this->getData(self::STATUS) ?? 'draft';
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): QuoteExtensionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get is active status
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * Set is active status
     *
     * @param bool $isActive
     * @return QuoteExtensionInterface
     */
    public function setIsActive(bool $isActive): QuoteExtensionInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getIsImmutable(): bool
    {
        return (bool)$this->getData(self::IS_IMMUTABLE);
    }

    /**
     * @inheritDoc
     */
    public function setIsImmutable(bool $isImmutable): QuoteExtensionInterface
    {
        return $this->setData(self::IS_IMMUTABLE, $isImmutable);
    }

    /**
     * @inheritDoc
     */
    public function getLockedAt(): ?string
    {
        return $this->getData(self::LOCKED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setLockedAt(?string $lockedAt): QuoteExtensionInterface
    {
        return $this->setData(self::LOCKED_AT, $lockedAt);
    }

    /**
     * @inheritDoc
     */
    public function getLockedByUserId(): ?string
    {
        return $this->getData(self::LOCKED_BY_USER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLockedByUserId(?string $lockedByUserId): QuoteExtensionInterface
    {
        return $this->setData(self::LOCKED_BY_USER_ID, $lockedByUserId);
    }

    /**
     * @inheritDoc
     */
    public function getImmutableHash(): ?string
    {
        return $this->getData(self::IMMUTABLE_HASH);
    }

    /**
     * @inheritDoc
     */
    public function setImmutableHash(?string $immutableHash): QuoteExtensionInterface
    {
        return $this->setData(self::IMMUTABLE_HASH, $immutableHash);
    }

    /**
     * @inheritDoc
     */
    public function getBusinessRules(): ?string
    {
        return $this->getData(self::BUSINESS_RULES);
    }

    /**
     * @inheritDoc
     */
    public function setBusinessRules(?string $businessRules): QuoteExtensionInterface
    {
        return $this->setData(self::BUSINESS_RULES, $businessRules);
    }

    /**
     * @inheritDoc
     */
    public function getProtectionSettings(): array
    {
        $data = $this->getData(self::PROTECTION_SETTINGS);
        return $data ? (is_string($data) ? json_decode($data, true) : $data) : [];
    }

    /**
     * @inheritDoc
     */
    public function setProtectionSettings(array $protectionSettings): QuoteExtensionInterface
    {
        return $this->setData(self::PROTECTION_SETTINGS, json_encode($protectionSettings));
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
    public function setAllowItemModification(bool $allowItemModification): QuoteExtensionInterface
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
    public function setAllowPriceModification(bool $allowPriceModification): QuoteExtensionInterface
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
    public function setAllowQuantityModification(bool $allowQuantityModification): QuoteExtensionInterface
    {
        return $this->setData(self::ALLOW_QUANTITY_MODIFICATION, $allowQuantityModification);
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
    public function setExpiresAt(?string $expiresAt): QuoteExtensionInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
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
    public function setName(?string $name): QuoteExtensionInterface
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
    public function setDescription(?string $description): QuoteExtensionInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        $data = $this->getData(self::METADATA);
        return $data ? (is_string($data) ? json_decode($data, true) : $data) : [];
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(array $metadata): QuoteExtensionInterface
    {
        try {
            $jsonMetadata = json_encode($metadata);
            if ($jsonMetadata === false) {
                throw new \InvalidArgumentException('Invalid metadata format');
            }
            return $this->setData(self::METADATA, $jsonMetadata);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to encode metadata: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(?string $createdAt): QuoteExtensionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(?string $updatedAt): QuoteExtensionInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function isImmutable(): bool
    {
        return $this->getIsImmutable();
    }

    /**
     * @inheritDoc
     */
    public function isExpired(): bool
    {
        if (!$this->getData(self::EXPIRES_AT)) {
            return false;
        }
        
        $expiresAt = new \DateTime($this->getData(self::EXPIRES_AT));
        return $expiresAt <= new \DateTime();
    }

    /**
     * @inheritDoc
     */
    public function setIsExpired(bool $isExpired): QuoteExtensionInterface
    {
        // This is a computed property, but we can set expires_at based on this
        if ($isExpired) {
            $this->setData(self::EXPIRES_AT, (new \DateTime())->format('Y-m-d H:i:s'));
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTermsAccepted(): bool
    {
        return (bool)$this->getData(self::TERMS_ACCEPTED);
    }

    /**
     * @inheritDoc
     */
    public function setTermsAccepted(bool $termsAccepted): QuoteExtensionInterface
    {
        return $this->setData(self::TERMS_ACCEPTED, $termsAccepted);
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
    public function setCustomerReference(?string $customerReference): QuoteExtensionInterface
    {
        return $this->setData(self::CUSTOMER_REFERENCE, $customerReference);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFee(): float
    {
        return (float)$this->getData(self::CUSTOM_FEE) ?: 0.0;
    }

    /**
     * @inheritDoc
     */
    public function setCustomFee(float $customFee): QuoteExtensionInterface
    {
        return $this->setData(self::CUSTOM_FEE, $customFee);
    }

    /**
     * @inheritDoc
     */
    public function getSortingOrder(): int
    {
        return (int)$this->getData(self::SORTING_ORDER) ?: 0;
    }

    /**
     * @inheritDoc
     */
    public function setSortingOrder(int $sortingOrder): QuoteExtensionInterface
    {
        return $this->setData(self::SORTING_ORDER, $sortingOrder);
    }

    /**
     * @inheritDoc
     */
    public function getImmutableCreatedBy(): ?int
    {
        return $this->getData(self::CREATED_BY_USER_ID) ? (int)$this->getData(self::CREATED_BY_USER_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setImmutableCreatedBy(?int $adminUserId): QuoteExtensionInterface
    {
        return $this->setData(self::CREATED_BY_USER_ID, $adminUserId);
    }

    /**
     * @inheritDoc
     */
    public function getImmutableCreatedAt(): ?string
    {
        return $this->getCreatedAt();
    }

    /**
     * @inheritDoc
     */
    public function setImmutableCreatedAt(?string $createdAt): QuoteExtensionInterface
    {
        return $this->setCreatedAt($createdAt);
    }
}