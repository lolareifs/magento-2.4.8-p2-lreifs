<?php
/**
 * Lreifs Multiquotes Customer Name Column
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
use Magento\Framework\UrlInterface;

class CustomerName extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
                if (isset($item['customer_id']) && $item['customer_id']) {
                    $customerName = $item['customer_name'] ?? 'Customer #' . $item['customer_id'];
                    $customerEmail = $item['customer_email'] ?? '';
                    
                    $item[$this->getData('name')] = sprintf(
                        '<div><strong>%s</strong><br/><small>%s</small></div>',
                        $customerName,
                        $customerEmail
                    );
                } else {
                    $item[$this->getData('name')] = '<span class="grid-severity-minor"><span>Guest Customer</span></span>';
                }
            }
        }

        return $dataSource;
    }
}