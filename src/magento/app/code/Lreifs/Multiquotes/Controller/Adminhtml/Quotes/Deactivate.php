<?php
/**
 * Lreifs Multiquotes Deactivate Quote Controller
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

class Deactivate extends Action
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
                
                // Check if quote is immutable
                if ($quoteExtension->getIsImmutable()) {
                    $this->messageManager->addErrorMessage(
                        __('Cannot deactivate an immutable quote. Immutable quotes cannot be modified.')
                    );
                    return $resultRedirect;
                }
                
                $quoteExtension->setIsActive(false);
                
                // If quote is expired and we're deactivating it, ensure status reflects that
                if ($this->isQuoteExpired($quoteExtension) && $quoteExtension->getStatus() !== 'expired') {
                    $quoteExtension->setStatus('expired');
                }
                
                $this->quoteExtensionRepository->save($quoteExtension);
                
                $this->messageManager->addSuccessMessage(__('Quote has been deactivated successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deactivating the quote: %1', $e->getMessage()));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Quote ID is required.'));
        }
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

        return $resultRedirect;
    }

    /**
     * Check if admin has permissions to deactivate quotes
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Lreifs_Multiquotes::quotes_manage');
    }
}