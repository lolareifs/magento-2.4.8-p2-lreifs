<?php
/**
 * Lreifs Multiquotes Quote Expiration Service
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Service;

use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;
use Lreifs\Multiquotes\Service\Audit\AuditLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class QuoteExpirationService
{
    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;
    
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    
    /**
     * @var AuditLogger
     */
    private $auditLogger;
    
    /**
     * @var TimezoneInterface
     */
    private $timezone;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param CollectionFactory $collectionFactory
     * @param AuditLogger $auditLogger
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        CollectionFactory $collectionFactory,
        AuditLogger $auditLogger,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->collectionFactory = $collectionFactory;
        $this->auditLogger = $auditLogger;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }
    
    /**
     * Process expired quotes and deactivate them
     *
     * @param int $batchSize
     * @param bool $dryRun
     * @return array
     */
    public function processExpiredQuotes(int $batchSize = 100, bool $dryRun = false): array
    {
        $result = [
            'found' => 0,
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            // Get expired quotes that are still active
            $expiredQuotes = $this->getExpiredActiveQuotes($batchSize);
            $result['found'] = $expiredQuotes->getSize();
            
            if ($result['found'] === 0) {
                $this->logger->info('No expired active quotes found to process');
                return $result;
            }
            
            $this->logger->info('Found expired active quotes to process', [
                'count' => $result['found'],
                'batch_size' => $batchSize,
                'dry_run' => $dryRun
            ]);
            
            foreach ($expiredQuotes as $quoteExtension) {
                try {
                    if (!$dryRun) {
                        // Deactivate the quote
                        $quoteExtension->setIsActive(false);
                        $quoteExtension->setStatus('expired');
                        $quoteExtension->setUpdatedAt($this->timezone->date()->format('Y-m-d H:i:s'));
                        
                        // Save the changes
                        $this->quoteExtensionRepository->save($quoteExtension);
                        
                        // Log the automatic expiration
                        $this->auditLogger->logQuoteExpiration($quoteExtension, [
                            'expired_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
                            'expiration_date' => $quoteExtension->getExpiresAt(),
                            'automatic' => true,
                            'processed_by' => 'system_cron'
                        ]);
                    }
                    
                    $this->logger->debug('Processed expired quote', [
                        'quote_id' => $quoteExtension->getQuoteId(),
                        'entity_id' => $quoteExtension->getEntityId(),
                        'expires_at' => $quoteExtension->getExpiresAt(),
                        'customer_id' => $quoteExtension->getCustomerId(),
                        'dry_run' => $dryRun
                    ]);
                    
                    $result['processed']++;
                    
                } catch (\Exception $e) {
                    $result['failed']++;
                    $result['errors'][] = [
                        'quote_id' => $quoteExtension->getQuoteId(),
                        'entity_id' => $quoteExtension->getEntityId(),
                        'message' => $e->getMessage()
                    ];
                    
                    $this->logger->error('Failed to process expired quote', [
                        'quote_id' => $quoteExtension->getQuoteId(),
                        'entity_id' => $quoteExtension->getEntityId(),
                        'error' => $e->getMessage(),
                        'dry_run' => $dryRun
                    ]);
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process expired quotes batch', [
                'error' => $e->getMessage(),
                'batch_size' => $batchSize,
                'dry_run' => $dryRun
            ]);
            
            throw new LocalizedException(__('Failed to process expired quotes: %1', $e->getMessage()));
        }
    }
    
    /**
     * Get expired active quotes collection
     *
     * @param int $limit
     * @return \Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\Collection
     */
    private function getExpiredActiveQuotes(int $limit = 100)
    {
        $collection = $this->collectionFactory->create();
        
        // Current timestamp in UTC
        $currentDateTime = $this->timezone->date()->format('Y-m-d H:i:s');
        
        $collection->addFieldToFilter('is_active', 1)
                  ->addFieldToFilter('expires_at', ['notnull' => true])
                  ->addFieldToFilter('expires_at', ['lt' => $currentDateTime])
                  ->setPageSize($limit)
                  ->setCurPage(1);
        
        return $collection;
    }
    
    /**
     * Check if a specific quote extension is expired
     *
     * @param \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface $quoteExtension
     * @return bool
     */
    public function isQuoteExpired($quoteExtension): bool
    {
        $expiresAt = $quoteExtension->getExpiresAt();
        
        if (!$expiresAt) {
            return false;
        }
        
        try {
            $expirationDate = $this->timezone->date($expiresAt);
            $currentDate = $this->timezone->date();
            
            return $expirationDate <= $currentDate;
            
        } catch (\Exception $e) {
            $this->logger->error('Error checking quote expiration', [
                'quote_id' => $quoteExtension->getQuoteId(),
                'expires_at' => $expiresAt,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Force expire a specific quote (manual override)
     *
     * @param int $quoteId
     * @param array $context
     * @return bool
     * @throws LocalizedException
     */
    public function expireQuoteManually(int $quoteId, array $context = []): bool
    {
        try {
            $quoteExtension = $this->quoteExtensionRepository->getByQuoteId($quoteId);
            
            if (!$quoteExtension->getIsActive()) {
                throw new LocalizedException(__('Quote #%1 is already inactive', $quoteId));
            }
            
            // Deactivate the quote
            $quoteExtension->setIsActive(false);
            $quoteExtension->setStatus('expired');
            $quoteExtension->setUpdatedAt($this->timezone->date()->format('Y-m-d H:i:s'));
            
            // Save the changes
            $this->quoteExtensionRepository->save($quoteExtension);
            
            // Log the manual expiration
            $this->auditLogger->logQuoteExpiration($quoteExtension, array_merge([
                'expired_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
                'expiration_date' => $quoteExtension->getExpiresAt(),
                'automatic' => false,
                'manual_override' => true
            ], $context));
            
            $this->logger->info('Quote manually expired', [
                'quote_id' => $quoteId,
                'entity_id' => $quoteExtension->getEntityId(),
                'context' => $context
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to manually expire quote', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage(),
                'context' => $context
            ]);
            
            throw new LocalizedException(__('Failed to expire quote #%1: %2', $quoteId, $e->getMessage()));
        }
    }
}