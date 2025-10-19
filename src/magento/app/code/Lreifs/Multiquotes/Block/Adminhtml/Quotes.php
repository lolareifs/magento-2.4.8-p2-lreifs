<?php
/**
 * Lreifs Multiquotes Quotes Grid Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Quotes extends Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_quotes';
        $this->_blockGroup = 'Lreifs_Multiquotes';
        $this->_headerText = __('Manage Quotes');
        $this->_addButtonLabel = __('Create New Quote');
        parent::_construct();
    }

    /**
     * Get create url
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }
}