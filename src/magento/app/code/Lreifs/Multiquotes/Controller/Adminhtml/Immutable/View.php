<?php
/**
 * Lreifs Multiquotes View Immutable Quote Controller
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Controller\Adminhtml\Immutable;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Framework\Registry;

class View extends Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::immutable_quotes';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * View action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        
        try {
            $quoteExtension = $this->quoteExtensionRepository->get($id);
            $this->coreRegistry->register('current_quote_extension', $quoteExtension);

            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Lreifs_Multiquotes::immutable_quotes');
            $resultPage->getConfig()->getTitle()->prepend(__('View Immutable Quote #%1', $quoteExtension->getEntityId()));

            return $resultPage;

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Quote not found.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
    }
}