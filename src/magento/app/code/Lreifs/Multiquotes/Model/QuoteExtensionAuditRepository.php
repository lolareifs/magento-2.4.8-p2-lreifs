<?php
namespace Lreifs\Multiquotes\Model;

use Lreifs\Multiquotes\Model\QuoteExtensionAuditFactory;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtensionAudit;

class QuoteExtensionAuditRepository
{
    private $factory;
    private $resource;

    public function __construct(
        QuoteExtensionAuditFactory $factory,
        QuoteExtensionAudit $resource
    ) {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    public function save(array $data): void
    {
        // If quote_extension_id is missing or invalid, set to null
        if (empty($data['quote_extension_id']) || !is_numeric($data['quote_extension_id'])) {
            $data['quote_extension_id'] = null;
        } else {
            // Optionally validate existence in main table, but do not block logging
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from('lreifs_quote_extension', ['entity_id'])
                ->where('entity_id = ?', $data['quote_extension_id']);
            $result = $connection->fetchOne($select);
            if (!$result) {
                $data['quote_extension_id'] = null;
            }
        }
        $auditLog = $this->factory->create();
        $auditLog->setData($data);
        $this->resource->save($auditLog);
    }
}
