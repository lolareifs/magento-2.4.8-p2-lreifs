<?php
/**
 * Lreifs Multiquotes Expires At Column
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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ExpiresAt extends Column
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        array $components = [],
        array $data = []
    ) {
        $this->timezone = $timezone;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                if (isset($item[$this->getData('name')]) && $item[$this->getData('name')]) {
                    $expiresAt = $item[$this->getData('name')];
                    
                    try {
                        $expiresAtDate = new \DateTime($expiresAt);
                        $now = new \DateTime();
                        
                        // Format the date
                        $formattedDate = $this->timezone->date($expiresAtDate)->format('M j, Y H:i');
                        $item[$this->getData('name')] = $formattedDate;
                    } catch (\Exception $e) {
                        $item[$this->getData('name')] = '<span class="grid-severity-minor">' . __('Invalid Date') . '</span>';
                    }
                } else {
                    $item[$this->getData('name')] = '<span class="grid-severity-minor">No Expiration</span>';
                }
            }
        }

        return $dataSource;
    }
}