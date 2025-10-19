<?php
/**
 * Lreifs Multiquotes Status Source Model
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CONVERTED = 'converted';

    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::STATUS_DRAFT, 'label' => __('Draft')],
            ['value' => self::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => self::STATUS_INACTIVE, 'label' => __('Inactive')],
            ['value' => self::STATUS_EXPIRED, 'label' => __('Expired')],
            ['value' => self::STATUS_CONVERTED, 'label' => __('Converted')],
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_INACTIVE => __('Inactive'),
            self::STATUS_EXPIRED => __('Expired'),
            self::STATUS_CONVERTED => __('Converted'),
        ];
    }
}