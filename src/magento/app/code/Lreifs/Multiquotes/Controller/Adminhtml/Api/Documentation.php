<?php
/**
 * Lreifs Multiquotes API Documentation Controller
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Controller\Adminhtml\Api;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

/**
 * API Documentation controller
 */
class Documentation extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Lreifs_Multiquotes::api_docs';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Lreifs_Multiquotes::api_docs');
        $resultPage->addBreadcrumb(__('LREIFS'), __('LREIFS'));
        $resultPage->addBreadcrumb(__('API Documentation'), __('API Documentation'));
        $resultPage->getConfig()->getTitle()->prepend(__('API Documentation'));

        return $resultPage;
    }
}