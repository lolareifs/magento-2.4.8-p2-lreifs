<?php
/**
 * Lreifs Multiquotes Immutable Actions Column
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

class ImmutableActions extends Column
{
    const URL_PATH_VIEW = 'multiquotes/immutable/view';
    const URL_PATH_ACTIVATE = 'multiquotes/immutable/activate';
    const URL_PATH_DEACTIVATE = 'multiquotes/immutable/deactivate';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id']) && isset($item['quote_id'])) {
                    $item[$this->getData('name')] = [
                        'view' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_VIEW,
                                ['id' => $item['entity_id']] // View uses entity_id for extension details
                            ),
                            'label' => __('View')
                        ]
                    ];

                    // Add activate/deactivate action based on current status
                    // Use quote_id for activate/deactivate actions (API expects quote_id)
                    if (isset($item['is_active'])) {
                        if ((int)$item['is_active'] === 1) {
                            $item[$this->getData('name')]['deactivate'] = [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_DEACTIVATE,
                                    ['id' => $item['quote_id']] // Use quote_id for API consistency
                                ),
                                'label' => __('Deactivate'),
                                'confirm' => [
                                    'title' => __('Deactivate Quote'),
                                    'message' => __('Are you sure you want to deactivate this quote?')
                                ]
                            ];
                        } else {
                            $item[$this->getData('name')]['activate'] = [
                                'href' => $this->urlBuilder->getUrl(
                                    static::URL_PATH_ACTIVATE,
                                    ['id' => $item['quote_id']] // Use quote_id for API consistency
                                ),
                                'label' => __('Activate'),
                                'confirm' => [
                                    'title' => __('Activate Quote'),
                                    'message' => __('Are you sure you want to activate this quote? This will deactivate any other active quotes for this customer.')
                                ]
                            ];
                        }
                    }
                }
            }
        }

        return $dataSource;
    }
}