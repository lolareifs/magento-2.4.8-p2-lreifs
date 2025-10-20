<?php
namespace Lreifs\Multiquotes\Model;

use Magento\Framework\ObjectManagerInterface;

class QuoteExtensionAuditFactory
{
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = [])
    {
    return $this->objectManager->create(\Lreifs\Multiquotes\Model\AuditLog::class, $data);
    }
}
