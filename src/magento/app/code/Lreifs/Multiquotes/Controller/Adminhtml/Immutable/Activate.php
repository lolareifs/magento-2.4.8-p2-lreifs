<?php
/**
 * Lreifs Multiquotes Activate Immutable Quote Controller
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
use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;

class Activate extends Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::immutable_quotes';

    /**
     * @var QuoteExtensionManagementInterface
     */
    private $quoteExtensionManagement;

    /**
     * @param Context $context
     * @param QuoteExtensionManagementInterface $quoteExtensionManagement
     */
    public function __construct(
        Context $context,
        QuoteExtensionManagementInterface $quoteExtensionManagement
    ) {
        parent::__construct($context);
        $this->quoteExtensionManagement = $quoteExtensionManagement;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        try {
            $id = $this->getRequest()->getParam('id');
            if (!$id) {
                $this->messageManager->addErrorMessage(__('Quote ID is required.'));
                return $resultRedirect->setPath('*/*/');
            }

            // Delegate to API layer - all business logic is handled there
            // Note: Grid should pass quote_id, not entity_id
            $this->quoteExtensionManagement->activateQuote($id);

            $this->messageManager->addSuccessMessage(__('Quote has been activated successfully.'));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}