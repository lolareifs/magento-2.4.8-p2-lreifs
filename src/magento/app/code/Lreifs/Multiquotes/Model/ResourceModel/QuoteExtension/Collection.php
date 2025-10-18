<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension;

use Lreifs\Multiquotes\Model\QuoteExtension;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension as QuoteExtensionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * QuoteExtension Collection
 * 
 * Collection for handling multiple quote extension records
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'lreifs_quote_extension_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'quote_extension_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(QuoteExtension::class, QuoteExtensionResource::class);
    }

    /**
     * Filter by customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerFilter(int $customerId): self
    {
        $this->addFieldToFilter('customer_id', $customerId);
        return $this;
    }

    /**
     * Filter by quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function addQuoteFilter(int $quoteId): self
    {
        $this->addFieldToFilter('quote_id', $quoteId);
        return $this;
    }

    /**
     * Filter by status
     *
     * @param string|array $status
     * @return $this
     */
    public function addStatusFilter($status): self
    {
        $this->addFieldToFilter('status', $status);
        return $this;
    }

    /**
     * Filter immutable quotes only
     *
     * @return $this
     */
    public function addImmutableFilter(): self
    {
        $this->addFieldToFilter('is_immutable', 1);
        return $this;
    }

    /**
     * Filter active quotes only
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('status', 'active');
        return $this;
    }

    /**
     * Filter expired quotes
     *
     * @return $this
     */
    public function addExpiredFilter(): self
    {
        $this->addFieldToFilter('expires_at', ['notnull' => true]);
        $this->addFieldToFilter('expires_at', ['lt' => new \Zend_Db_Expr('NOW()')]);
        return $this;
    }

    /**
     * Filter by extension type
     *
     * @param string $extensionType
     * @return $this
     */
    public function addExtensionTypeFilter(string $extensionType): self
    {
        $this->addFieldToFilter('extension_type', $extensionType);
        return $this;
    }

    /**
     * Filter by date range
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function addDateFilter(string $from, string $to): self
    {
        $this->addFieldToFilter('created_at', ['from' => $from, 'to' => $to]);
        return $this;
    }

    /**
     * Join with quote table to get quote information
     *
     * @param array $fields
     * @return $this
     */
    public function joinQuoteTable(array $fields = []): self
    {
        if (empty($fields)) {
            $fields = [
                'quote_created_at' => 'created_at',
                'quote_updated_at' => 'updated_at',
                'quote_is_active' => 'is_active',
                'quote_items_count' => 'items_count',
                'quote_grand_total' => 'grand_total',
                'quote_currency_code' => 'quote_currency_code'
            ];
        }

        $this->getSelect()->joinLeft(
            ['quote' => $this->getTable('quote')],
            'main_table.quote_id = quote.entity_id',
            $fields
        );

        return $this;
    }

    /**
     * Join with customer table to get customer information
     *
     * @param array $fields
     * @return $this
     */
    public function joinCustomerTable(array $fields = []): self
    {
        if (empty($fields)) {
            $fields = [
                'customer_firstname' => 'firstname',
                'customer_lastname' => 'lastname',
                'customer_email' => 'email'
            ];
        }

        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'main_table.customer_id = customer.entity_id',
            $fields
        );

        return $this;
    }

    /**
     * Add summary data to collection
     *
     * @return $this
     */
    public function addSummaryData(): self
    {
        $this->getSelect()->columns([
            'days_since_created' => new \Zend_Db_Expr('DATEDIFF(NOW(), main_table.created_at)'),
            'days_until_expiry' => new \Zend_Db_Expr('CASE WHEN main_table.expires_at IS NOT NULL THEN DATEDIFF(main_table.expires_at, NOW()) ELSE NULL END'),
            'is_expired' => new \Zend_Db_Expr('CASE WHEN main_table.expires_at IS NOT NULL AND main_table.expires_at < NOW() THEN 1 ELSE 0 END')
        ]);

        return $this;
    }

    /**
     * Get collection statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $select = clone $this->getSelect();
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns([
            'total_count' => 'COUNT(*)',
            'immutable_count' => 'SUM(CASE WHEN is_immutable = 1 THEN 1 ELSE 0 END)',
            'active_count' => 'SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END)',
            'expired_count' => 'SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < NOW() THEN 1 ELSE 0 END)'
        ]);

        return $this->getConnection()->fetchRow($select) ?: [];
    }

    /**
     * Order by most recent first
     *
     * @return $this
     */
    public function orderByNewest(): self
    {
        $this->setOrder('created_at', 'DESC');
        return $this;
    }

    /**
     * Order by oldest first
     *
     * @return $this
     */
    public function orderByOldest(): self
    {
        $this->setOrder('created_at', 'ASC');
        return $this;
    }
}