<?php
namespace Lreifs\Multiquotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuoteExtensionAudit extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('lreifs_quote_extension_audit', 'audit_id');
    }
}
