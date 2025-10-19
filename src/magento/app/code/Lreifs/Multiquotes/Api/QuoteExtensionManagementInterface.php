<?php
/**
 * Lreifs Multiquotes Module v1.0.0 - Main Service Interface
 * 
 * Enterprise-grade immutable quote management interface providing comprehensive
 * business operations for creating, managing, and monitoring immutable quotes
 * with complete audit trails and advanced filtering capabilities.
 * 
 * Key Features:
 * - Create immutable quotes with multiple products
 * - Convert existing quotes to immutable state
 * - System-wide quote viewing with advanced filtering
 * - Complete lifecycle management (activate/deactivate/delete)
 * - Comprehensive audit trails and security logging
 * 
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Lreifs\Multiquotes\Api\Data\CreateImmutableQuoteRequestInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Quote Extension Management Interface
 * 
 * Main service interface for enterprise immutable quote management.
 * Provides comprehensive business operations with full audit capabilities
 * and enterprise-grade security controls.
 * 
 * @api
 * @since 1.0.0
 */
interface QuoteExtensionManagementInterface
{
    /**
     * Create a new immutable quote with multiple products
     * 
     * Creates a completely new quote with auto-generated ID and converts it
     * immediately to immutable state. Supports multiple products with
     * SKU validation and proper inventory checks.
     *
     * @param CreateImmutableQuoteRequestInterface $request Complete quote creation request with customer and items
     * @return QuoteExtensionInterface The created immutable quote extension
     * @throws LocalizedException If validation fails or products are invalid
     * @throws NoSuchEntityException If customer or products don't exist
     * @since 1.0.0
     */
    public function createImmutableQuoteWithItems(CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface;

    /**
     * Convert existing Magento quote to immutable state
     * 
     * Takes an existing quote (from cart or manual creation) and converts it
     * to immutable state, preserving all existing items and customer data.
     * Useful for converting shopping carts to locked quotes.
     *
     * @param int $quoteId Existing Magento quote ID to convert
     * @param CreateImmutableQuoteRequestInterface $request Additional configuration for conversion (name, description, etc.)
     * @return QuoteExtensionInterface The converted immutable quote extension
     * @throws NoSuchEntityException If the quote doesn't exist or is invalid
     * @throws LocalizedException If the quote cannot be converted (already immutable, etc.)
     * @since 1.0.0
     */
    public function convertQuoteToImmutable(int $quoteId, CreateImmutableQuoteRequestInterface $request): QuoteExtensionInterface;

    /**
     * Activate an immutable quote for customer use
     * 
     * Makes the quote available for customer viewing and order conversion.
     * Logs the activation action with admin user information and IP tracking.
     *
     * @param int $quoteId The immutable quote ID to activate
     * @param int|null $adminUserId Admin user performing the action (auto-detected if null)
     * @return bool True on successful activation
     * @throws NoSuchEntityException If the quote doesn't exist
     * @throws LocalizedException If activation fails or quote is already active
     * @since 1.0.0
     */
    public function activateQuote(int $quoteId, ?int $adminUserId = null): bool;

    /**
     * Deactivate an immutable quote
     * 
     * Removes the quote from customer view and prevents order conversion.
     * Maintains quote data for audit purposes while disabling functionality.
     *
     * @param int $quoteId The immutable quote ID to deactivate
     * @param int|null $adminUserId Admin user performing the action (auto-detected if null)
     * @return bool True on successful deactivation
     * @throws NoSuchEntityException If the quote doesn't exist  
     * @throws LocalizedException If deactivation fails or quote is already inactive
     * @since 1.0.0
     */
    public function deactivateQuote(int $quoteId, ?int $adminUserId = null): bool;

    /**
     * Activate an immutable quote by extension ID
     * 
     * Activates a quote using the quote extension entity_id instead of the core quote_id.
     * Useful for admin interfaces that work with extension records directly.
     *
     * @param int $extensionId The quote extension entity_id to activate
     * @param int|null $adminUserId Admin user performing the action (auto-detected if null)
     * @return bool True on successful activation
     * @throws NoSuchEntityException If the quote extension doesn't exist
     * @throws LocalizedException If activation fails
     * @since 1.0.0
     */
    public function activateQuoteByExtensionId(int $extensionId, ?int $adminUserId = null): bool;

    /**
     * Deactivate an immutable quote by extension ID
     * 
     * Deactivates a quote using the quote extension entity_id instead of the core quote_id.
     * Useful for admin interfaces that work with extension records directly.
     *
     * @param int $extensionId The quote extension entity_id to deactivate
     * @param int|null $adminUserId Admin user performing the action (auto-detected if null)
     * @return bool True on successful deactivation
     * @throws NoSuchEntityException If the quote extension doesn't exist
     * @throws LocalizedException If deactivation fails
     * @since 1.0.0
     */
    public function deactivateQuoteByExtensionId(int $extensionId, ?int $adminUserId = null): bool;

    /**
     * Get customer's quotes with optional filtering
     * 
     * Retrieves all quotes associated with a specific customer.
     * Can optionally filter to show only immutable quotes.
     *
     * @param int $customerId The customer ID to get quotes for
     * @param bool|null $immutableOnly True to show only immutable quotes, false for all quotes, null for default behavior
     * @return QuoteExtensionInterface[] Array of quote extensions for the customer
     * @throws LocalizedException If customer ID is invalid or access is denied
     * @since 1.0.0
     */
    public function getCustomerQuotes(int $customerId, ?bool $immutableOnly = null): array;

    /**
     * Convert immutable quote to Magento order
     * 
     * Creates a new order from the immutable quote, preserving all
     * items, pricing, and customer information. The quote remains
     * immutable after conversion for audit purposes.
     *
     * @param int $quoteId The immutable quote ID to convert
     * @param int $customerId The customer ID for validation and order creation
     * @return int The created order ID
     * @throws NoSuchEntityException If quote or customer doesn't exist
     * @throws LocalizedException If conversion fails or quote is inactive
     * @since 1.0.0
     */
    public function convertToOrder(int $quoteId, int $customerId): int;

    /**
     * Check if quote modifications are allowed for specific actions
     * 
     * Validates whether a particular action is allowed on the quote
     * based on its immutable state and current status.
     *
     * @param int $quoteId The quote ID to check
     * @param string $action The action to validate (e.g., 'add_product', 'update_quantity')
     * @return bool True if the action is allowed, false if blocked
     * @throws NoSuchEntityException If the quote doesn't exist
     * @since 1.0.0
     */
    public function canModifyQuote(int $quoteId, string $action): bool;

    /**
     * Get immutable quote extension by quote ID
     * 
     * Retrieves the quote extension data for a specific quote,
     * including immutability status and metadata.
     *
     * @param int $quoteId The Magento quote ID to get extension data for
     * @return QuoteExtensionInterface The quote extension data
     * @throws NoSuchEntityException If the quote or extension doesn't exist
     * @since 1.0.0
     */
    public function getByQuoteId(int $quoteId): QuoteExtensionInterface;

    /**
     * Delete immutable quote permanently
     * 
     * Removes the quote and all associated data from the system.
     * This action is irreversible and should be used with caution.
     * Comprehensive audit logging tracks the deletion.
     *
     * @param int $quoteId The immutable quote ID to delete
     * @param int|null $adminUserId Admin user performing the deletion (auto-detected if null)
     * @return bool True on successful deletion
     * @throws NoSuchEntityException If the quote doesn't exist
     * @throws LocalizedException If deletion fails or is not permitted
     * @since 1.0.0
     */
    public function deleteQuote(int $quoteId, ?int $adminUserId = null): bool;

    /**
     * Get all system quotes with advanced filtering (Admin-only endpoint)
     * 
     * FEATURE: Enhanced endpoint that provides system-wide access to both
     * immutable and normal Magento quotes with comprehensive filtering options.
     * Includes pagination and supports 6 different filter types.
     *
     * Available filters:
     * - is_immutable: Filter by immutable status (1/0)
     * - customer_id: Filter by specific customer
     * - is_active: Filter by active status (1/0)  
     * - store_id: Filter by store
     * - created_from/created_to: Date range filtering
     * 
     * @param int $pageSize Number of quotes per page (default: 20)
     * @param int $currentPage Page number to retrieve (default: 1)
     * @param array $filters Associative array of filters to apply
     * @return array Array containing 'quotes', 'total_count', 'page_size', 'current_page'
     * @throws LocalizedException If filters are invalid or access is denied
     * @since 1.0.0
     */
    public function getAllImmutableQuotes(int $pageSize = 20, int $currentPage = 1, array $filters = []): array;
}