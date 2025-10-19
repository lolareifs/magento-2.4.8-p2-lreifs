<?php
/**
 * Lreifs Multiquotes Expiration Service
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Service;

use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Model\Source\Status;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ExpirationService
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param CollectionFactory $collectionFactory
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        DateTime $dateTime
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * Check if quote is expired
     *
     * @param \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface $quote
     * @return bool
     */
    public function isQuoteExpired($quote): bool
    {
        if (!$quote->getExpiresAt()) {
            return false;
        }

        $currentTimestamp = $this->dateTime->gmtTimestamp();
        $expiresTimestamp = strtotime($quote->getExpiresAt());
        
        return $expiresTimestamp < $currentTimestamp;
    }

    /**
     * Update all expired quotes
     *
     * @return int Number of quotes updated
     */
    public function updateExpiredQuotes(): int
    {
        $currentTimestamp = $this->dateTime->gmtTimestamp();
        
        $expiredQuotes = $this->collectionFactory->create()
            ->addFieldToFilter('expires_at', ['notnull' => true])
            ->addFieldToFilter('expires_at', ['lt' => date('Y-m-d H:i:s', $currentTimestamp)])
            ->addFieldToFilter('status', ['neq' => Status::STATUS_EXPIRED]);

        $updatedCount = 0;
        foreach ($expiredQuotes as $quote) {
            $quote->setStatus(Status::STATUS_EXPIRED);
            $quote->setIsActive(0);
            $this->quoteExtensionRepository->save($quote);
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Deactivate all other active quotes for customer
     *
     * @param int $customerId
     * @param int $excludeQuoteId
     * @return int Number of quotes deactivated
     */
    public function deactivateOtherCustomerQuotes(int $customerId, int $excludeQuoteId): int
    {
        $otherQuotes = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('entity_id', ['neq' => $excludeQuoteId])
            ->addFieldToFilter('is_active', 1);

        $deactivatedCount = 0;
        foreach ($otherQuotes as $otherQuote) {
            $otherQuote->setIsActive(0);
            
            // Check if this quote is expired, if so set status to expired, otherwise inactive
            if ($this->isQuoteExpired($otherQuote)) {
                $otherQuote->setStatus(Status::STATUS_EXPIRED);
            } else {
                $otherQuote->setStatus(Status::STATUS_INACTIVE);
            }
            
            $this->quoteExtensionRepository->save($otherQuote);
            $deactivatedCount++;
        }

        return $deactivatedCount;
    }
}