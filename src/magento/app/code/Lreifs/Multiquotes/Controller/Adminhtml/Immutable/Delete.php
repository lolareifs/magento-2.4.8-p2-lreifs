<?php
/**
 * Lreifs Multiquotes Delete Immutable Quote Controller
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Controller\Adminhtml\Immutable;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;

class Delete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::immutable_delete';

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
     * Delete action for immutable quote
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $this->quoteExtensionRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('You deleted the immutable quote extension.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find an immutable quote extension to delete.'));
        }
        $resultRedirect->setPath('multiquotes/immutable/index');
        return $resultRedirect;
    }
}
