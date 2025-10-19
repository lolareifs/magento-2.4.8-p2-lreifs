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
                $quoteExtension->setIsActive(true);
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
     * Check if admin has permissions to activate quotes
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Lreifs_Multiquotes::quotes_manage');
    }
}