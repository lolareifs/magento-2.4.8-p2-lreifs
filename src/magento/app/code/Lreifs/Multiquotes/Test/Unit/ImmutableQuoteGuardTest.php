<?php
declare(strict_types=1);

namespace Lreifs\Multiquotes\Test\Unit;

use PHPUnit\Framework\TestCase;
use Lreifs\Multiquotes\Service\Guard\ImmutableQuoteGuard;
use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;

class ImmutableQuoteGuardTest extends TestCase
{
    public function testPreventsMutationAndDispatchesEvent()
    {
        $repository = $this->getMockBuilder(\Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteExtension = $this->getMockBuilder(\Lreifs\Multiquotes\Model\QuoteExtension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteExtension->method('isImmutable')->willReturn(true);
        $repository->method('getByQuoteId')->willReturn($quoteExtension);
        $auditLogger = $this->getMockBuilder(\Lreifs\Multiquotes\Service\Audit\AuditLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventManager = $this->createMock(ManagerInterface::class);
        $remoteAddress = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adminSession = $this->getMockBuilder(\Magento\Backend\Model\Auth\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock(LoggerInterface::class);

        $eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('lreifs_quote_modification_blocked'),
                $this->arrayHasKey('extension')
            );
        $logger->expects($this->once())
            ->method('warning');

        $guard = new ImmutableQuoteGuard(
            $repository,
            $auditLogger,
            $eventManager,
            $remoteAddress,
            $adminSession,
            $customerSession,
            $logger
        );
        // Simula el método preventModification
        // Simula que el quote es protegido y fuerza el dispatch
            $eventManager->expects($this->once())
                ->method('dispatch')
                ->with(
                    $this->equalTo('lreifs_quote_modification_blocked'),
                    $this->arrayHasKey('extension')
                );
    // Espera la excepción de inmutabilidad
    $this->expectException(\Lreifs\Multiquotes\Exception\ImmutableQuoteModificationException::class);
    $guard->preventModification(1, 'add_product', ['admin_user_id' => 1, 'prevention_reason' => 'quote_is_immutable']);
    }
}
