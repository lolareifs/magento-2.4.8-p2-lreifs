<?php
namespace Lreifs\Multiquotes\Model;

use Magento\Framework\Model\AbstractModel;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtensionAudit;

class AuditLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(QuoteExtensionAudit::class);
    }
}
