<?php
declare(strict_types=1);

namespace Lreifs\Multiquotes\Test\Unit;

use PHPUnit\Framework\TestCase;
use Lreifs\Multiquotes\Service\Audit\AuditLogger;
use Psr\Log\LoggerInterface;

class AuditLoggerTest extends TestCase
{
    public function testLogsAuditEntry()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $securityLogger = $this->createMock(LoggerInterface::class);
        $auditRepository = $this->getMockBuilder(\Lreifs\Multiquotes\Model\QuoteExtensionAuditRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Immutable quote created'));

        $auditLogger = new AuditLogger($logger, $securityLogger, $auditRepository);

        // Simula un QuoteExtensionInterface para el log
        $extension = $this->getMockBuilder(\Lreifs\Multiquotes\Model\QuoteExtension::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteId', 'getEntityId'])
            ->getMock();
        $extension->method('getQuoteId')->willReturn(123);
        $extension->method('getEntityId')->willReturn(456);

        $auditLogger->logQuoteCreation($extension, ['customer_id' => 1, 'admin_user_id' => 2, 'ip_address' => '127.0.0.1']);
    }
}
