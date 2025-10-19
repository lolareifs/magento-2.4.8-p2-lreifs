<?php
/**
 * Lreifs Multiquotes Edit Controller
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
use Magento\Framework\View\Result\PageFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;

class Edit extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::quote_extension_view';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    protected $quoteExtensionRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        
        try {
            if ($id) {
                $quoteExtension = $this->quoteExtensionRepository->get($id);
                
                /** @var \Magento\Framework\View\Result\Page $resultPage */
                $resultPage = $this->resultPageFactory->create();
                $resultPage->setActiveMenu('Lreifs_Multiquotes::quotes');
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Quote Extension'));
                $resultPage->getConfig()->getTitle()->prepend(
                    __('Quote Extension #%1', $quoteExtension->getEntityId())
                );

                return $resultPage;
            } else {
                $this->messageManager->addErrorMessage(__('This quote extension no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('This quote extension no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }
}