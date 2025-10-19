<?php
namespace Lreifs\Multiquotes\Controller\Adminhtml\Immutable;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Show under construction page for Add New Immutable Quote
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Crear Immutable Quote'));
        return $resultPage;
    }
}
