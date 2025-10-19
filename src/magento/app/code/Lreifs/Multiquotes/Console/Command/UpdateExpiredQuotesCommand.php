<?php
/**
 * Lreifs Multiquotes Update Expired Quotes Command
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lreifs\Multiquotes\Model\ResourceModel\QuoteExtension\CollectionFactory;
use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Model\Source\Status;
use Magento\Framework\Stdlib\DateTime\DateTime;

class UpdateExpiredQuotesCommand extends Command
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QuoteExtensionRepositoryInterface
     */
    private $quoteExtensionRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param CollectionFactory $collectionFactory
     * @param QuoteExtensionRepositoryInterface $quoteExtensionRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        QuoteExtensionRepositoryInterface $quoteExtensionRepository,
        DateTime $dateTime
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->quoteExtensionRepository = $quoteExtensionRepository;
        $this->dateTime = $dateTime;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('multiquotes:update-expired')
            ->setDescription('Update expired quotes status and deactivate them');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentTimestamp = $this->dateTime->gmtTimestamp();
        
        $expiredQuotes = $this->collectionFactory->create()
            ->addFieldToFilter('expires_at', ['notnull' => true])
            ->addFieldToFilter('expires_at', ['lt' => date('Y-m-d H:i:s', $currentTimestamp)])
            ->addFieldToFilter('status', ['neq' => Status::STATUS_EXPIRED]);

        $updatedCount = 0;
        foreach ($expiredQuotes as $quote) {
            $quote->setStatus(Status::STATUS_EXPIRED);
            $quote->setIsActive(0);
            $this->quoteExtensionRepository->save($quote);
            $updatedCount++;
        }

        $output->writeln(sprintf('Updated %d expired quotes.', $updatedCount));
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}