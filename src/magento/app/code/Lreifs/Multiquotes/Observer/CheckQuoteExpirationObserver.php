<?php
/**
 * Lreifs Multiquotes Quote Access Observer
 *
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Lreifs\Multiquotes\Service\QuoteExpirationService;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Psr\Log\LoggerInterface;

class CheckQuoteExpirationObserver implements ObserverInterface
{
    /**
     * @var QuoteExpirationService
     */
    private $expirationService;
    
    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param QuoteExpirationService $expirationService
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteExpirationService $expirationService,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        LoggerInterface $logger
    ) {
        $this->expirationService = $expirationService;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->logger = $logger;
    }
    
    /**
     * Check if quote is expired when accessed and deactivate if necessary
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface $quoteExtension */
            $quoteExtension = $observer->getEvent()->getData('quote_extension');
            
            if (!$quoteExtension || !$quoteExtension->getEntityId()) {
                return;
            }
            
            // Skip if quote is already inactive
            if (!$quoteExtension->getIsActive()) {
                return;
            }
            
            // Skip if no expiration date is set
            if (!$quoteExtension->getExpiresAt()) {
                return;
            }
            
            // Check if quote is expired
            if ($this->expirationService->isQuoteExpired($quoteExtension)) {
                $this->logger->info('Quote expired during access, deactivating', [
                    'quote_id' => $quoteExtension->getQuoteId(),
                    'entity_id' => $quoteExtension->getEntityId(),
                    'expires_at' => $quoteExtension->getExpiresAt(),
                    'customer_id' => $quoteExtension->getCustomerId()
                ]);
                
                // Deactivate the expired quote
                $quoteExtension->setIsActive(false);
                $quoteExtension->setStatus('expired');
                
                // Save the changes
                $this->quoteExtensionRepository->save($quoteExtension);
                
                // Update the observer event data with the modified quote
                $observer->getEvent()->setData('quote_extension', $quoteExtension);
                
                $this->logger->info('Quote automatically deactivated due to expiration', [
                    'quote_id' => $quoteExtension->getQuoteId(),
                    'entity_id' => $quoteExtension->getEntityId(),
                    'triggered_by' => 'real_time_access'
                ]);
            }
            
        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid breaking the main process
            $this->logger->error('Error checking quote expiration in observer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}