<?php
/**
 * Lreifs Multiquotes Dashboard Grid Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;

class Grid extends Extended
{
    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->setId('multiquotes_dashboard_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        // TODO: Implement actual collection when quote model is ready
        $collection = new \Magento\Framework\Data\Collection();
        
        // Sample data for now
        $collection->addItem(new \Magento\Framework\DataObject([
            'id' => 1,
            'quote_id' => 1,
            'customer_id' => 101,
            'customer_name' => 'John Doe',
            'status' => 'pending',
            'total' => '$1,250.00',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ]));
        
        $collection->addItem(new \Magento\Framework\DataObject([
            'id' => 2,
            'quote_id' => 2,
            'customer_id' => 102,
            'customer_name' => 'Jane Smith',
            'status' => 'approved',
            'total' => '$850.00',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ]));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('quote_id', [
            'header' => __('Quote ID'),
            'index' => 'quote_id',
            'type' => 'number',
            'width' => '80px'
        ]);

        $this->addColumn('customer_id', [
            'header' => __('Customer ID'),
            'index' => 'customer_id',
            'type' => 'number',
            'width' => '100px'
        ]);

        $this->addColumn('customer_name', [
            'header' => __('Customer'),
            'index' => 'customer_name'
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => [
                'pending' => __('Pending'),
                'approved' => __('Approved'),
                'rejected' => __('Rejected'),
                'expired' => __('Expired')
            ]
        ]);

        $this->addColumn('total', [
            'header' => __('Total'),
            'index' => 'total',
            'type' => 'currency'
        ]);

        $this->addColumn('created_at', [
            'header' => __('Created At'),
            'index' => 'created_at',
            'type' => 'datetime'
        ]);

        return parent::_prepareColumns();
    }

    /**
     * Get grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('multiquotes/dashboard/grid', ['_current' => true]);
    }
}