<?php
/**
 * Lreifs Multiquotes Status Column
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

class Status extends Column
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
                if (isset($item[$this->getData('name')])) {
                    $status = $item[$this->getData('name')];
                    $item[$this->getData('name')] = $this->formatStatusBadge($status);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Format status with appropriate badge
     *
     * @param string $status
     * @return string
     */
    private function formatStatusBadge($status)
    {
        // Treat 'draft' as 'inactive' for badge and label
        if ($status === 'draft') {
            $status = 'inactive';
        }
        $badges = [
            'active' => '<span class="grid-severity-notice"><span>Active</span></span>',
            'inactive' => '<span class="grid-severity-minor"><span>Inactive</span></span>',
            'expired' => '<span class="grid-severity-critical"><span>Expired</span></span>',
            'converted' => '<span class="grid-severity-notice"><span>Converted</span></span>'
        ];

        return $badges[$status] ?? '<span class="grid-severity-minor"><span>' . ucfirst($status) . '</span></span>';
    }
}