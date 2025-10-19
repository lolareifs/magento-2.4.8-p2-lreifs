<?php
/**
 * Lreifs Multiquotes Mass Status Controller
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

class MassStatus extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Lreifs_Multiquotes::quote_extension_create';

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
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $status = $this->getRequest()->getParam('status');
        $collectionSize = $collection->getSize();
        $updatedItems = 0;

        foreach ($collection as $quoteExtension) {
            try {
                $quoteExtension->setStatus($status);
                $this->quoteExtensionRepository->save($quoteExtension);
                $updatedItems++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Cannot update quote extension %1. Reason: %2', $quoteExtension->getId(), $e->getMessage())
                );
            }
        }

        if ($updatedItems) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been updated.', $updatedItems)
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}