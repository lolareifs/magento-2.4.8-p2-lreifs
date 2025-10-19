<?php
/**
 * Lreifs Multiquotes Dashboard Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Block\Adminhtml;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Lreifs\Multiquotes\Helper\Config;

class Dashboard extends Container
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $configHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->buttonList->add(
            'new_quote',
            [
                'label' => __('Create New Quote'),
                'onclick' => "setLocation('" . $this->getUrl('multiquotes/quotes/new') . "')",
                'class' => 'primary'
            ]
        );

        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                \Lreifs\Multiquotes\Block\Adminhtml\Dashboard\Grid::class,
                'multiquotes.dashboard.grid'
            )
        );

        return parent::_prepareLayout();
    }

    /**
     * Get total quotes count
     *
     * @return int
     */
    public function getTotalQuotes()
    {
        // TODO: Implement actual quote counting
        return 0;
    }

    /**
     * Get pending quotes count
     *
     * @return int
     */
    public function getPendingQuotes()
    {
        // TODO: Implement actual pending quote counting
        return 0;
    }

    /**
     * Get approved quotes count
     *
     * @return int
     */
    public function getApprovedQuotes()
    {
        // TODO: Implement actual approved quote counting
        return 0;
    }

    /**
     * Get rate limit status
     *
     * @return bool
     */
    public function isRateLimitEnabled()
    {
        return $this->configHelper->isRateLimitingEnabled();
    }

    /**
     * Get requests per hour limit
     *
     * @return int
     */
    public function getRequestsPerHour()
    {
        return $this->configHelper->getRequestsPerHour();
    }
}