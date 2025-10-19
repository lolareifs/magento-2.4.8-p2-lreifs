<?php
/**
 * Lreifs Multiquotes Expire Quotes Command
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
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\Console\Cli;
use Lreifs\Multiquotes\Service\QuoteExpirationService;
use Psr\Log\LoggerInterface;

class ExpireQuotesCommand extends Command
{
    const DRY_RUN_OPTION = 'dry-run';
    const BATCH_SIZE_OPTION = 'batch-size';
    
    /**
     * @var QuoteExpirationService
     */
    private $expirationService;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param QuoteExpirationService $expirationService
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        QuoteExpirationService $expirationService,
        LoggerInterface $logger,
        string $name = null
    ) {
        $this->expirationService = $expirationService;
        $this->logger = $logger;
        parent::__construct($name);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('lreifs:quotes:expire')
            ->setDescription('Process and deactivate expired quotes automatically')
            ->addOption(
                self::DRY_RUN_OPTION,
                'd',
                InputOption::VALUE_NONE,
                'Dry run mode - show what would be processed without making changes'
            )
            ->addOption(
                self::BATCH_SIZE_OPTION,
                'b',
                InputOption::VALUE_REQUIRED,
                'Number of quotes to process in each batch',
                100
            );
        
        parent::configure();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption(self::DRY_RUN_OPTION);
        $batchSize = (int)$input->getOption(self::BATCH_SIZE_OPTION);
        
        $startTime = microtime(true);
        
        $output->writeln('<info>Starting expired quotes processing...</info>');
        $output->writeln(sprintf('Mode: %s', $isDryRun ? '<comment>DRY RUN</comment>' : '<info>LIVE</info>'));
        $output->writeln(sprintf('Batch Size: <comment>%d</comment>', $batchSize));
        $output->writeln('');
        
        try {
            $result = $this->expirationService->processExpiredQuotes($batchSize, $isDryRun);
            
            $executionTime = microtime(true) - $startTime;
            
            // Output results
            $output->writeln('<info>Processing Results:</info>');
            $output->writeln(sprintf('  Found expired quotes: <comment>%d</comment>', $result['found']));
            $output->writeln(sprintf('  Successfully processed: <comment>%d</comment>', $result['processed']));
            $output->writeln(sprintf('  Failed to process: <comment>%d</comment>', $result['failed']));
            $output->writeln(sprintf('  Execution time: <comment>%.2f seconds</comment>', $executionTime));
            
            if (!empty($result['errors'])) {
                $output->writeln('');
                $output->writeln('<error>Errors encountered:</error>');
                foreach ($result['errors'] as $error) {
                    $output->writeln(sprintf('  - Quote #%d: %s', $error['quote_id'], $error['message']));
                }
            }
            
            if ($isDryRun && $result['found'] > 0) {
                $output->writeln('');
                $output->writeln('<comment>This was a dry run. No quotes were actually modified.</comment>');
                $output->writeln('<comment>Run without --dry-run to apply changes.</comment>');
            }
            
            // Log execution
            $this->logger->info('Expired quotes processing completed', [
                'dry_run' => $isDryRun,
                'batch_size' => $batchSize,
                'found' => $result['found'],
                'processed' => $result['processed'],
                'failed' => $result['failed'],
                'execution_time' => $executionTime,
                'errors' => $result['errors']
            ]);
            
            return $result['failed'] > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error processing expired quotes: %s</error>', $e->getMessage()));
            
            $this->logger->error('Expired quotes processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dry_run' => $isDryRun,
                'batch_size' => $batchSize
            ]);
            
            return Cli::RETURN_FAILURE;
        }
    }
}