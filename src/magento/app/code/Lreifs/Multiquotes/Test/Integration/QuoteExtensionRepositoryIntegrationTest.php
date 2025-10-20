<?php
declare(strict_types=1);

namespace Lreifs\Multiquotes\Test\Integration;

use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;

class QuoteExtensionRepositoryIntegrationTest extends TestCase
{
    public function testSaveAndGetQuoteExtension()
    {
        // Mock del repositorio y del modelo
        $repository = $this->getMockBuilder(QuoteExtensionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteExtension = $this->getMockBuilder(QuoteExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    $quoteExtension->method('getEntityId')->willReturn(1);
    $repository->method('save')->willReturn($quoteExtension);
    $repository->method('get')->willReturn($quoteExtension);

    $saved = $repository->save($quoteExtension);
    $this->assertInstanceOf(QuoteExtensionInterface::class, $saved);
    $loaded = $repository->get($saved->getEntityId());
    $this->assertEquals($saved->getEntityId(), $loaded->getEntityId());
    }
}
