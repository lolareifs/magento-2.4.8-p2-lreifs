<?php
/**
 * Lreifs Multiquotes View Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Block\Adminhtml\Quotes;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class View extends Template
{
    /**
     * @var QuoteExtensionRepositoryInterface
     */
    protected $quoteExtensionRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface
     */
    protected $quoteExtension;

    /**
     * @param Context $context
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->cartRepository = $cartRepository;
        $this->customerRepository = $customerRepository;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get quote extension
     *
     * @return \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface|null
     */
    public function getQuoteExtension()
    {
        if (!$this->quoteExtension) {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                try {
                    $this->quoteExtension = $this->quoteExtensionRepository->get($id);
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
        return $this->quoteExtension;
    }

    /**
     * Get quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface|null
     */
    public function getQuote()
    {
        $quoteExtension = $this->getQuoteExtension();
        if ($quoteExtension && $quoteExtension->getQuoteId()) {
            try {
                return $this->cartRepository->get($quoteExtension->getQuoteId());
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        $quoteExtension = $this->getQuoteExtension();
        if ($quoteExtension && $quoteExtension->getCustomerId()) {
            try {
                return $this->customerRepository->getById($quoteExtension->getCustomerId());
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Format currency
     *
     * @param float $amount
     * @return string
     */
    public function formatCurrency($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * Get edit URL
     *
     * @return string
     */
    public function getEditUrl()
    {
        $quoteExtension = $this->getQuoteExtension();
        if ($quoteExtension) {
            return $this->getUrl('*/*/edit', ['id' => $quoteExtension->getEntityId()]);
        }
        return '';
    }

    /**
     * Get delete URL
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $quoteExtension = $this->getQuoteExtension();
        if ($quoteExtension) {
            return $this->getUrl('*/*/delete', ['id' => $quoteExtension->getEntityId()]);
        }
        return '';
    }
}