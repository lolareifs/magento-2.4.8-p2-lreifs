<?php
/**
 * Lreifs Multiquotes API Version Source Model
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApiVersion implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'v1', 'label' => __('Version 1.0 (Stable)')],
            ['value' => 'v1.1', 'label' => __('Version 1.1 (Enhanced)')],
            ['value' => 'v2', 'label' => __('Version 2.0 (Beta)')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'v1' => __('Version 1.0 (Stable)'),
            'v1.1' => __('Version 1.1 (Enhanced)'),
            'v2' => __('Version 2.0 (Beta)'),
        ];
    }
}