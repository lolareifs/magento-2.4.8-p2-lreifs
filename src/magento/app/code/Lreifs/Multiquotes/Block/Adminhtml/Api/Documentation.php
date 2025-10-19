<?php
/**
 * Lreifs Multiquotes API Documentation Block
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Block\Adminhtml\Api;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\ModuleListInterface;

class Documentation extends Template
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
     * Get module version
     */
    public function getModuleVersion(): string
    {
        $moduleInfo = $this->moduleList->getOne('Lreifs_Multiquotes');
        return $moduleInfo['setup_version'] ?? '1.0.0';
    }

    /**
     * Get formatted module version for display
     */
    public function getFormattedVersion(): string
    {
        return 'v' . $this->getModuleVersion();
    }
}