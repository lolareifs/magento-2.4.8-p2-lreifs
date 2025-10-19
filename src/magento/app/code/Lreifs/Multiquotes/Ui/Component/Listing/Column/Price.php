<?php
/**
 * Lreifs Multiquotes Price Column
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
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Price extends Column
{
    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceHelper $priceHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceHelper $priceHelper,
        array $components = [],
        array $data = []
    ) {
        $this->priceHelper = $priceHelper;
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
                if (isset($item[$this->getData('name')])) {
                    if (is_string($item[$this->getData('name')]) && strpos($item[$this->getData('name')], '$') !== false) {
                        // Already formatted
                        continue;
                    }
                    
                    $price = (float) $item[$this->getData('name')];
                    $item[$this->getData('name')] = $this->priceHelper->currency($price, true, false);
                }
            }
        }

        return $dataSource;
    }
}