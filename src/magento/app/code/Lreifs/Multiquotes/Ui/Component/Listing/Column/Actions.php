<?php
/**
 * Lreifs Multiquotes Actions Column
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

class Actions extends Column
{
    const URL_PATH_EDIT = 'multiquotes/quotes/edit';
    const URL_PATH_DELETE = 'multiquotes/quotes/delete';
    const URL_PATH_VIEW = 'multiquotes/quotes/view';
    const URL_PATH_ACTIVATE = 'multiquotes/quotes/activate';
    const URL_PATH_DEACTIVATE = 'multiquotes/quotes/deactivate';

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
                if (isset($item['entity_id'])) {
                    $actions = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['entity_id']
                                ]
                            ),
                            'label' => __('Edit')
                        ],
                        'view' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_VIEW,
                                [
                                    'id' => $item['entity_id']
                                ]
                            ),
                            'label' => __('View Details')
                        ]
                    ];

                    // Add activate/deactivate based on current status
                    $isActive = isset($item['is_active']) ? (bool)$item['is_active'] : false;
                    
                    if ($isActive) {
                        $actions['deactivate'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DEACTIVATE,
                                [
                                    'id' => $item['entity_id']
                                ]
                            ),
                            'label' => __('Deactivate'),
                            'confirm' => [
                                'title' => __('Deactivate Quote'),
                                'message' => __('Are you sure you want to deactivate this quote?')
                            ]
                        ];
                    } else {
                        $actions['activate'] = [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_ACTIVATE,
                                [
                                    'id' => $item['entity_id']
                                ]
                            ),
                            'label' => __('Activate'),
                            'confirm' => [
                                'title' => __('Activate Quote'),
                                'message' => __('Are you sure you want to activate this quote?')
                            ]
                        ];
                    }

                    $actions['delete'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_DELETE,
                            [
                                'id' => $item['entity_id']
                            ]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Quote Extension'),
                            'message' => __('Are you sure you want to delete this quote extension?')
                        ]
                    ];

                    $item[$this->getData('name')] = $actions;
                }
            }
        }

        return $dataSource;
    }
}