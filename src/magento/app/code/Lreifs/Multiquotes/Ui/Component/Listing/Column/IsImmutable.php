<?php
/**
 * Lreifs Multiquotes Is Immutable Column
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

class IsImmutable extends Column
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
                
                if (isset($item[$fieldName]) || array_key_exists($fieldName, $item)) {
                    $isImmutable = (bool)$item[$fieldName];
                    
                    if ($isImmutable) {
                        $item[$fieldName] = '<span class="grid-severity-critical" style="background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;"><span>Immutable</span></span>';
                    } else {
                        $item[$fieldName] = '<span class="grid-severity-notice" style="background-color: #17a2b8; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;"><span>Mutable</span></span>';
                    }
                } else {
                    // Default value if field is missing
                    $item[$fieldName] = '<span class="grid-severity-minor" style="background-color: #6c757d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;"><span>Unknown</span></span>';
                }
            }
        }

        return $dataSource;
    }
}