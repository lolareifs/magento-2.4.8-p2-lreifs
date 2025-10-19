<?php
/**
 * Lreifs Multiquotes Mass Delete Controller for Immutable Quotes
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
use Magento\Framework\Controller\ResultFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Ui\Component\MassAction\Filter;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;

class MassDelete extends Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::immutable_quotes';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;

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
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
    }

    /**
     * Execute mass delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->collectionFactory->create();
        
        // Filter only immutable quotes for consistency
        $collection->addFieldToFilter('is_immutable', 1);
        
        $collection = $this->filter->getCollection($collection);
        $deletedCount = 0;

        foreach ($collection as $quote) {
            try {
                $this->quoteExtensionRepository->delete($quote);
                $deletedCount++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error deleting quote ID %1: %2', $quote->getEntityId(), $e->getMessage()));
            }
        }

        if ($deletedCount > 0) {
            $this->messageManager->addSuccessMessage(__('%1 quote(s) have been deleted.', $deletedCount));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}