<?php
declare(strict_types=1);

namespace Lreifs\Multiquotes\Test\Unit;

use PHPUnit\Framework\TestCase;
use Lreifs\Multiquotes\Model\QuoteExtensionRepository;
use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;

class QuoteExtensionRepositoryTest extends TestCase
{
    public function testSaveDispatchesEventsAndLogs()
    {
        // Usar un stub de AbstractModel para simular el modelo
            $quoteExtension = $this->getMockBuilder(\Lreifs\Multiquotes\Model\QuoteExtension::class)
                ->disableOriginalConstructor()
                ->getMock();
        $resource = $this->getMockBuilder(\Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = $this->getMockBuilder(\Lreifs\Multiquotes\Model\QuoteExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionFactory = $this->getMockBuilder(\Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultsFactory = $this->getMockBuilder(\Magento\Framework\Api\SearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionProcessor = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventManager = $this->createMock(ManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo('multiquotes_quoteextension_save_after'),
                $this->arrayHasKey('quote_extension')
            );
        $logger->expects($this->once())
            ->method('info');

        $cache = $this->getMockBuilder(\Magento\Framework\App\CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository = new QuoteExtensionRepository(
            $resource,
            $factory,
            $collectionFactory,
            $searchResultsFactory,
            $collectionProcessor,
            $eventManager,
            $logger,
            $cache
        );
        $repository->save($quoteExtension);
    }
}
