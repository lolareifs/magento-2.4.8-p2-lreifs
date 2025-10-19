<?php
/**
 * Lreifs Multiquotes Activate Quote Controller
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Controller\Adminhtml\Quotes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;

class Activate extends Action
{
    /**
     * @var QuoteExtensionRepositoryInterface
     */
    protected $quoteExtensionRepository;

    /**
     * @param Context $context
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     */
    public function __construct(
        Context $context,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository
    ) {
        parent::__construct($context);
        $this->quoteExtensionRepository = $quoteExtensionRepository;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $quoteExtension = $this->quoteExtensionRepository->get($id);
                
                // Check if quote is expired before allowing activation
                if ($this->isQuoteExpired($quoteExtension)) {
                    $expirationDate = $quoteExtension->getExpiresAt() 
                        ? date('M j, Y H:i', strtotime($quoteExtension->getExpiresAt())) 
                        : 'N/A';
                    
                    $this->messageManager->addErrorMessage(
                        __('Cannot activate expired quote. Quote expired on: %1. Please extend the expiration date first.', $expirationDate)
                    );
                    return $resultRedirect;
                }
                
                // Check if quote is already in expired status
                if ($quoteExtension->getStatus() === 'expired') {
                    $this->messageManager->addErrorMessage(
                        __('Cannot activate a quote with expired status. Please change the status first.')
                    );
                    return $resultRedirect;
                }
                
                // Check if quote is immutable
                if ($quoteExtension->getIsImmutable()) {
                    $this->messageManager->addErrorMessage(
                        __('Cannot activate an immutable quote. Immutable quotes cannot be modified.')
                    );
                    return $resultRedirect;
                }
                
                $quoteExtension->setIsActive(true);
                
                // If status is currently expired, change it to active
                if ($quoteExtension->getStatus() === 'expired') {
                    $quoteExtension->setStatus('active');
                }
                
                $this->quoteExtensionRepository->save($quoteExtension);
                
                $this->messageManager->addSuccessMessage(__('Quote has been activated successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while activating the quote: %1', $e->getMessage()));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Quote ID is required.'));
        }

        return $resultRedirect;
    }

    /**
     * Check if quote is expired
     *
     * @param \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface $quoteExtension
     * @return bool
     */
    private function isQuoteExpired($quoteExtension): bool
    {
        // If no expiration date is set, it's not expired
        if (!$quoteExtension->getExpiresAt()) {
            return false;
        }

        try {
            $expirationDate = new \DateTime($quoteExtension->getExpiresAt());
            $currentDate = new \DateTime();
            
            return $expirationDate < $currentDate;
        } catch (\Exception $e) {
            // If we can't parse the date, assume it's not expired to be safe
            return false;
        }
    }

    /**
     * Check if admin has permissions to activate quotes
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Lreifs_Multiquotes::quotes_manage');
    }
}