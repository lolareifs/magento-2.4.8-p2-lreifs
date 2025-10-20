<?php
/**
 * Lreifs Multiquotes DTO - Create Immutable Quote Request
 *
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api\Data;

/**
 * Create Immutable Quote Request Interface
 * DTO for creating immutable quotes
 */
interface CreateImmutableQuoteRequestInterface
{
    public const QUOTE_ID = 'quote_id';
    public const CUSTOMER_ID = 'customer_id';
    public const CUSTOMER_EMAIL = 'customer_email';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const NOTES = 'notes';
    public const EXPIRES_AT = 'expires_at';
    public const ALLOW_ITEM_MODIFICATION = 'allow_item_modification';
    public const ALLOW_PRICE_MODIFICATION = 'allow_price_modification';
    public const ALLOW_QUANTITY_MODIFICATION = 'allow_quantity_modification';
    public const BUSINESS_RULES = 'business_rules';
    public const METADATA = 'metadata';
    public const PROTECTION_SETTINGS = 'protection_settings';
    public const FORCE_IMMUTABLE = 'force_immutable';
    public const AUTO_ACTIVATE = 'auto_activate';
    public const CUSTOMER_REFERENCE = 'customer_reference';
    public const CUSTOM_FEE = 'custom_fee';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const IP_ADDRESS = 'ip_address';
    public const USER_AGENT = 'user_agent';
    public const ITEMS = 'items';

    /**
     * Get quote ID
     *
     * @return int
     */
    public function getQuoteId(): int;

    /**
     * Set quote ID
     *
     * @param int|null $quoteId
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setQuoteId(?int $quoteId): CreateImmutableQuoteRequestInterface;

    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setCustomerId(int $customerId): CreateImmutableQuoteRequestInterface;

    /**
     * Get customer email
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string;

    /**
     * Set customer email
     *
     * @param string|null $customerEmail
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setCustomerEmail(?string $customerEmail): CreateImmutableQuoteRequestInterface;

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set name
     *
     * @param string|null $name
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setName(?string $name): CreateImmutableQuoteRequestInterface;

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description
     *
     * @param string|null $description
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setDescription(?string $description): CreateImmutableQuoteRequestInterface;

    /**
     * Get notes
     *
     * @return string|null
     */
    public function getNotes(): ?string;

    /**
     * Set notes
     *
     * @param string|null $notes
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setNotes(?string $notes): CreateImmutableQuoteRequestInterface;

    /**
     * Get expires at
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set expires at
     *
     * @param string|null $expiresAt
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setExpiresAt(?string $expiresAt): CreateImmutableQuoteRequestInterface;

    /**
     * Get allow item modification
     *
     * @return bool
     */
    public function getAllowItemModification(): bool;

    /**
     * Set allow item modification
     *
     * @param bool $allowItemModification
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setAllowItemModification(bool $allowItemModification): CreateImmutableQuoteRequestInterface;

    /**
     * Get allow price modification
     *
     * @return bool
     */
    public function getAllowPriceModification(): bool;

    /**
     * Set allow price modification
     *
     * @param bool $allowPriceModification
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setAllowPriceModification(bool $allowPriceModification): CreateImmutableQuoteRequestInterface;

    /**
     * Get allow quantity modification
     *
     * @return bool
     */
    public function getAllowQuantityModification(): bool;

    /**
     * Set allow quantity modification
     *
     * @param bool $allowQuantityModification
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setAllowQuantityModification(bool $allowQuantityModification): CreateImmutableQuoteRequestInterface;

    /**
     * Get business rules
     *
     * @return mixed
     */
    public function getBusinessRules(): array;

    /**
     * Set business rules
     *
     * @param mixed $businessRules
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setBusinessRules(array $businessRules): CreateImmutableQuoteRequestInterface;

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
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setMetadata(array $metadata): CreateImmutableQuoteRequestInterface;

    /**
     * Get protection settings
     *
     * @return mixed
     */
    public function getProtectionSettings(): array;

    /**
     * Set protection settings
     *
     * @param mixed $protectionSettings
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setProtectionSettings(array $protectionSettings): CreateImmutableQuoteRequestInterface;

    /**
     * Get force immutable
     *
     * @return bool
     */
    public function getForceImmutable(): bool;

    /**
     * Set force immutable
     *
     * @param bool $forceImmutable
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setForceImmutable(bool $forceImmutable): CreateImmutableQuoteRequestInterface;

    /**
     * Get auto activate
     *
     * @return bool
     */
    public function getAutoActivate(): bool;

    /**
     * Set auto activate
     *
     * @param bool $autoActivate
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setAutoActivate(bool $autoActivate): CreateImmutableQuoteRequestInterface;

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
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setCustomerReference(?string $customerReference): CreateImmutableQuoteRequestInterface;

    /**
     * Get custom fee
     *
     * @return float|null
     */
    public function getCustomFee(): ?float;

    /**
     * Set custom fee
     *
     * @param float|null $customFee
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setCustomFee(?float $customFee): CreateImmutableQuoteRequestInterface;

    /**
     * Get admin user ID
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set admin user ID
     *
     * @param int|null $adminUserId
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setAdminUserId(?int $adminUserId): CreateImmutableQuoteRequestInterface;

    /**
     * Get IP address
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Set IP address
     *
     * @param string|null $ipAddress
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setIpAddress(?string $ipAddress): CreateImmutableQuoteRequestInterface;

    /**
     * Get user agent
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Set user agent
     *
     * @param string|null $userAgent
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setUserAgent(?string $userAgent): CreateImmutableQuoteRequestInterface;

    /**
     * Get items for quote creation
     *
     * @return \Lreifs\Multiquotes\Api\Data\QuoteItemRequestInterface[]|null
     */
    public function getItems();

    /**
     * Set items for quote creation
     *
     * @param \Lreifs\Multiquotes\Api\Data\QuoteItemRequestInterface[]|null $items
     * @return CreateImmutableQuoteRequestInterface
     */
    public function setItems($items): CreateImmutableQuoteRequestInterface;
}