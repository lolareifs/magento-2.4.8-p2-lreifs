<?php
/**
 * Lreifs Multiquotes Mass Delete Controller
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
use Magento\Framework\Controller\ResultFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Ui\Component\MassAction\Filter;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;

class MassDelete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::quote_extension_delete';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    protected $quoteExtensionRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();
            $deletedItems = 0;

            foreach ($collection as $quoteExtension) {
                try {
                    $this->quoteExtensionRepository->delete($quoteExtension);
                    $deletedItems++;
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __('Cannot delete quote extension %1. Reason: %2', $quoteExtension->getId(), $e->getMessage())
                    );
                }
            }

            if ($deletedItems) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been deleted.', $deletedItems)
                );
            }
        } catch (\Exception $e) {
            // Fallback: If collection/repository fails, try direct ID-based deletion
            $selected = $this->getRequest()->getParam('selected');
            $excluded = $this->getRequest()->getParam('excluded');
            
            if (!$selected && $excluded !== 'false') {
                $this->messageManager->addErrorMessage(__('Please select item(s) to delete.'));
            } else {
                $deletedCount = 0;
                if (is_array($selected)) {
                    foreach ($selected as $id) {
                        try {
                            $quoteExtension = $this->quoteExtensionRepository->get((int)$id);
                            $this->quoteExtensionRepository->delete($quoteExtension);
                            $deletedCount++;
                        } catch (\Exception $deleteError) {
                            $this->messageManager->addErrorMessage(
                                __('Cannot delete quote extension %1. Reason: %2', $id, $deleteError->getMessage())
                            );
                        }
                    }
                }
                
                if ($deletedCount > 0) {
                    $this->messageManager->addSuccessMessage(
                        __('A total of %1 record(s) have been deleted.', $deletedCount)
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('No records were deleted. Error: %1', $e->getMessage())
                    );
                }
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}