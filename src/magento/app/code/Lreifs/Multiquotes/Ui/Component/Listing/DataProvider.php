<?php
/**
 * Lreifs Multiquotes Grid Data Provider
 * Optimized to use REST APIs for data retrieval
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Ui\Component\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as AbstractDataProvider;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $cartRepository
     * @param PriceHelper $priceHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $cartRepository,
        PriceHelper $priceHelper,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->customerRepository = $customerRepository;
        $this->cartRepository = $cartRepository;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get data using Repository API
     *
     * @return array
     */
    public function getData()
    {
        $searchCriteria = $this->getSearchCriteria();
        
        // Use the repository API to get quote extensions
        $searchResult = $this->quoteExtensionRepository->getList($searchCriteria);
        
        $data = [];
        foreach ($searchResult->getItems() as $quoteExtension) {
            $item = $quoteExtension->getData();
            
            // Enhance data with customer and quote information
            $item = $this->enhanceItemData($item, $quoteExtension);
            
            $data[] = $item;
        }

        return [
            'totalRecords' => $searchResult->getTotalCount(),
            'items' => $data
        ];
    }





    /**
     * Enhance item data with additional information
     *
     * @param array $item
     * @param \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface $quoteExtension
     * @return array
     */
    private function enhanceItemData($item, $quoteExtension)
    {
        // Add customer information
        if ($quoteExtension->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($quoteExtension->getCustomerId());
                $item['customer_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
                $item['customer_email'] = $customer->getEmail();
            } catch (\Exception $e) {
                $item['customer_name'] = __('Customer #%1', $quoteExtension->getCustomerId());
                $item['customer_email'] = '';
            }
        } else {
            $item['customer_name'] = __('Guest');
            $item['customer_email'] = '';
        }

        // Add quote information
        if ($quoteExtension->getQuoteId()) {
            try {
                $quote = $this->cartRepository->get($quoteExtension->getQuoteId());
                $item['grand_total'] = $quote->getGrandTotal(); // Keep as number for Price column formatter
                $item['items_count'] = $quote->getItemsCount();
                $item['currency_code'] = $quote->getQuoteCurrencyCode();
            } catch (\Exception $e) {
                $item['grand_total'] = 0;
                $item['items_count'] = 0;
                $item['currency_code'] = '';
            }
        } else {
            $item['grand_total'] = 0;
            $item['items_count'] = 0;
            $item['currency_code'] = '';
        }

        // Use is_active from the quote extension table (not from core quote)
        $isActiveValue = $quoteExtension->getIsActive();
        $item['is_active'] = (int)($isActiveValue !== null ? $isActiveValue : 0);
        
        // Use is_immutable from the quote extension table
        $isImmutableValue = $quoteExtension->getIsImmutable();
        $item['is_immutable'] = (int)($isImmutableValue !== null ? $isImmutableValue : 0);
        
        // Add status field
        $item['status'] = $quoteExtension->getStatus() ?? 'pending';

        // Format expires_at - keep original timestamp
        $item['expires_at'] = $quoteExtension->getExpiresAt();

        return $item;
    }

    /**
     * Format status with appropriate badge
     *
     * @param string $status
     * @return string
     */
    private function formatStatusBadge($status)
    {
        $badges = [
            'draft' => '<span class="grid-severity-minor"><span>Draft</span></span>',
            'active' => '<span class="grid-severity-notice"><span>Active</span></span>',
            'inactive' => '<span class="grid-severity-minor"><span>Inactive</span></span>',
            'expired' => '<span class="grid-severity-critical"><span>Expired</span></span>',
            'converted' => '<span class="grid-severity-notice"><span>Converted</span></span>'
        ];

        return $badges[$status] ?? '<span class="grid-severity-minor"><span>' . ucfirst($status) . '</span></span>';
    }
}