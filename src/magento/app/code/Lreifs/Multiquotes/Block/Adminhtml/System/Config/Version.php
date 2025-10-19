<?php
/**
 * Lreifs Multiquotes Version Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

class Version extends Field
{
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }

    /**
     * Remove scope label
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $moduleInfo = $this->moduleList->getOne('Lreifs_Multiquotes');
        $version = $moduleInfo['setup_version'] ?? '1.0.0';
        
        $html = '<div class="control-value" style="padding-top: 8px;">';
        $html .= '<strong style="color: #eb5202;">' . __('Version %1', $version) . '</strong>';
        $html .= '<br /><small style="color: #666;">' . __('Lreifs Multiquotes Enterprise Module') . '</small>';
        $html .= '</div>';
        
        return $html;
    }
}