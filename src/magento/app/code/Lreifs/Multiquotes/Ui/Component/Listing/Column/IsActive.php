<?php
/**
 * Lreifs Multiquotes Is Active Column
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class IsActive extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $fieldName = $this->getData('name');
                
                // Check multiple possible field names for is_active
                $isActive = null;
                if (isset($item['is_active'])) {
                    $isActive = $item['is_active'];
                } elseif (isset($item['IsActive'])) {
                    $isActive = $item['IsActive'];
                } elseif (isset($item[$fieldName])) {
                    $isActive = $item[$fieldName];
                } else {
                    // Default to 0 if not found
                    $isActive = 0;
                }
                
                // Convert to boolean and format
                $isActiveBool = (bool)((int)$isActive);
                
                if ($isActiveBool) {
                    $item[$fieldName] = '<span class="grid-severity-notice" style="background-color: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;"><span>Yes</span></span>';
                } else {
                    $item[$fieldName] = '<span class="grid-severity-critical" style="background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;"><span>No</span></span>';
                }
            }
        }

        return $dataSource;
    }
}