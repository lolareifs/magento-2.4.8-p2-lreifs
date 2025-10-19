<?php
/**
 * Lreifs Multiquotes Immutable Quotes Data Provider
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Ui\Component\Listing\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;

class ImmutableQuotes extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var FilterPool
     */
    protected $filterPool;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterPool $filterPool
     * @param CollectionFactory $collectionFactory
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
        FilterPool $filterPool,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->filterPool = $filterPool;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get collection
     *
     * @return \Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();
            
            // Join customer table to get customer name
            $this->collection->getSelect()->joinLeft(
                ['customer' => $this->collection->getTable('customer_entity')],
                'main_table.customer_id = customer.entity_id',
                [
                    'customer_firstname' => 'customer.firstname',
                    'customer_lastname' => 'customer.lastname',
                    'customer_email' => 'customer.email'
                ]
            );

            // Join quote table to get quote information
            $this->collection->getSelect()->joinLeft(
                ['quote' => $this->collection->getTable('quote')],
                'main_table.quote_id = quote.entity_id',
                []
            );

            // Filter only immutable quotes (is_immutable = 1)
            $this->collection->addFieldToFilter('main_table.is_immutable', 1);

            // Add computed customer_name field
            $this->collection->getSelect()->columns([
                'customer_name' => "CONCAT(COALESCE(customer.firstname, ''), ' ', COALESCE(customer.lastname, ''))"
            ]);

            // Ensure main_table.entity_id is used to avoid ambiguity
            $this->collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->collection->getSelect()->columns([
                'entity_id' => 'main_table.entity_id',
                'quote_id' => 'main_table.quote_id',
                'customer_id' => 'main_table.customer_id',
                'is_active' => 'main_table.is_active',
                'is_immutable' => 'main_table.is_immutable',
                'status' => 'main_table.status',
                'expires_at' => 'main_table.expires_at',
                'created_at' => 'main_table.created_at',
                'updated_at' => 'main_table.updated_at',
                'customer_firstname' => 'customer.firstname',
                'customer_lastname' => 'customer.lastname',
                'customer_email' => 'customer.email',
                'customer_name' => "CONCAT(COALESCE(customer.firstname, ''), ' ', COALESCE(customer.lastname, ''))"
            ]);
        }

        return $this->collection;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();
        
        $data = [];
        foreach ($collection as $item) {
            $itemData = $item->getData();
            
            // Ensure is_active is properly cast to integer
            $itemData['is_active'] = (int) $item->getIsActive();
            
            // Clean up customer name (remove extra spaces)
            if (isset($itemData['customer_name'])) {
                $itemData['customer_name'] = trim($itemData['customer_name']);
            }
            
            $data[] = $itemData;
        }

        return [
            'totalRecords' => $collection->getSize(),
            'items' => $data
        ];
    }

    /**
     * Add filter
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        if ($filter->getField() == 'customer_name') {
            $this->getCollection()->getSelect()->having(
                "CONCAT(COALESCE(customer.firstname, ''), ' ', COALESCE(customer.lastname, '')) LIKE ?",
                '%' . $filter->getValue() . '%'
            );
        } elseif ($filter->getField() == 'entity_id') {
            // Handle entity_id filter with proper table prefix and IN clause syntax
            $conditionType = $filter->getConditionType();
            $value = $filter->getValue();
            
            if ($conditionType === 'in' && is_array($value)) {
                $this->getCollection()->getSelect()->where(
                    'main_table.entity_id IN (?)',
                    $value
                );
            } else {
                $this->getCollection()->addFieldToFilter('main_table.entity_id', [$conditionType => $value]);
            }
        } else {
            // Prefix main_table for main table fields to avoid ambiguity
            $fieldName = $filter->getField();
            if (in_array($fieldName, ['quote_id', 'customer_id', 'is_active', 'is_immutable', 'status', 'expires_at', 'created_at', 'updated_at'])) {
                $fieldName = 'main_table.' . $fieldName;
            }
            
            $this->getCollection()->addFieldToFilter($fieldName, [$filter->getConditionType() => $filter->getValue()]);
        }
    }
}