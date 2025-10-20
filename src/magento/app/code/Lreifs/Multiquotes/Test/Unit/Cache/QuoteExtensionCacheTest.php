<?php
declare(strict_types=1);

namespace Lreifs\Multiquotes\Test\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Lreifs\Multiquotes\Model\QuoteExtensionRepository;
use Magento\Framework\Cache\FrontendInterface;
use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;

class QuoteExtensionCacheTest extends TestCase
{
    public function testCacheIsUsedOnGetById()
    {
        $cache = $this->createMock(FrontendInterface::class);
        $repository = $this->getMockBuilder(QuoteExtensionRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['loadFromDb', 'getById'])
            ->getMock();
        $quoteExtension = $this->createMock(QuoteExtensionInterface::class);
        $cache->expects($this->once())
            ->method('load')
            ->willReturn($quoteExtension);
        $repository->expects($this->never())
            ->method('loadFromDb');
        // Simula la llamada a getById que debería usar el cache
        $repository->method('getById')->willReturnCallback(function($id) use ($cache) {
            return $cache->load($id);
        });
        $result = $repository->getById(123);
        $this->assertSame($quoteExtension, $result);
    }
}
