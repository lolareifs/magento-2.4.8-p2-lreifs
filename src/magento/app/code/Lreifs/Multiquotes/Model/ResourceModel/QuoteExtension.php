<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * QuoteExtension Resource Model
 * 
 * Handles database operations for quote extensions
 */
class QuoteExtension extends AbstractDb
{
    /**
     * Table name
     */
    const TABLE_NAME = 'lreifs_quote_extension';

    /**
     * Primary key field
     */
    const ID_FIELD_NAME = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }

    /**
     * Get quote extension by quote ID
     *
     * @param int $quoteId
     * @param string $extensionType
     * @return array
     */
    public function getByQuoteId(int $quoteId, string $extensionType = 'immutable'): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('quote_id = ?', $quoteId)
            ->where('extension_type = ?', $extensionType);

        return $connection->fetchRow($select) ?: [];
    }

    /**
     * Get immutable quotes by customer
     *
     * @param int $customerId
     * @return array
     */
    public function getImmutableQuotesByCustomer(int $customerId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('is_immutable = ?', 1)
            ->order('created_at DESC');

        return $connection->fetchAll($select);
    }

    /**
     * Get expired quotes
     *
     * @return array
     */
    public function getExpiredQuotes(): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('expires_at IS NOT NULL')
            ->where('expires_at < NOW()')
            ->where('status != ?', 'expired');

        return $connection->fetchAll($select);
    }

    /**
     * Get quotes by status
     *
     * @param string $status
     * @return array
     */
    public function getQuotesByStatus(string $status): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('status = ?', $status);

        return $connection->fetchAll($select);
    }

    /**
     * Update quote status
     *
     * @param int $quoteExtensionId
     * @param string $status
     * @return int
     */
    public function updateStatus(int $quoteExtensionId, string $status): int
    {
        $connection = $this->getConnection();
        return $connection->update(
            $this->getMainTable(),
            ['status' => $status, 'updated_at' => new \Zend_Db_Expr('NOW()')],
            ['entity_id = ?' => $quoteExtensionId]
        );
    }

    /**
     * Check if quote is immutable
     *
     * @param int $quoteId
     * @return bool
     */
    public function isQuoteImmutable(int $quoteId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['is_immutable'])
            ->where('quote_id = ?', $quoteId)
            ->where('is_immutable = ?', 1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Get quote extension statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $connection = $this->getConnection();
        
        $totalSelect = $connection->select()
            ->from($this->getMainTable(), [
                'total_quotes' => 'COUNT(*)',
                'immutable_quotes' => 'SUM(CASE WHEN is_immutable = 1 THEN 1 ELSE 0 END)',
                'active_quotes' => 'SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END)',
                'expired_quotes' => 'SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END)'
            ]);

        return $connection->fetchRow($totalSelect) ?: [];
    }
}