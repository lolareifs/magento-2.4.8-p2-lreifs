<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Console\Command;

use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Magento\Framework\Console\Cli;

/**
 * CLI Command to delete immutable quotes
 */
class DeleteQuoteCommand extends Command
{
    private const ARGUMENT_QUOTE_ID = 'quote-id';
    private const OPTION_ADMIN_USER_ID = 'admin-user-id';
    private const OPTION_FORCE = 'force';

    public function __construct(
        private readonly QuoteExtensionManagementInterface $quoteExtensionManagement
    ) {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('lreifs:multiquotes:delete')
            ->setDescription('Delete an immutable quote')
            ->addArgument(
                self::ARGUMENT_QUOTE_ID,
                InputArgument::REQUIRED,
                'Quote ID to delete'
            )
            ->addOption(
                self::OPTION_ADMIN_USER_ID,
                'a',
                InputOption::VALUE_OPTIONAL,
                'Admin user ID performing the deletion'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Force deletion without confirmation'
            );

        parent::configure();
    }

    /**
     * Execute command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Get input values
            $quoteId = (int)$input->getArgument(self::ARGUMENT_QUOTE_ID);
            $adminUserId = $input->getOption(self::OPTION_ADMIN_USER_ID) ? 
                (int)$input->getOption(self::OPTION_ADMIN_USER_ID) : null;
            $force = $input->getOption(self::OPTION_FORCE);

            // Validate inputs
            if ($quoteId <= 0) {
                $io->error('Quote ID must be a positive integer');
                return Cli::RETURN_FAILURE;
            }

            $io->title('Deleting Immutable Quote');
            $io->section('Request Details:');
            $io->definitionList(
                ['Quote ID' => $quoteId],
                ['Admin User ID' => $adminUserId ?? 'System'],
                ['Force delete' => $force ? 'Yes' : 'No']
            );

            // Confirmation if not forced
            if (!$force) {
                $confirmation = $io->confirm(
                    'Are you sure you want to delete this immutable quote? This action cannot be undone.',
                    false
                );
                
                if (!$confirmation) {
                    $io->info('Deletion cancelled by user.');
                    return Cli::RETURN_SUCCESS;
                }
            }

            // Delete the quote
            $io->text('Processing deletion...');
            $success = $this->quoteExtensionManagement->deleteQuote($quoteId, $adminUserId);

            if ($success) {
                // Display success result
                $io->success('Immutable quote deleted successfully!');
                $io->section('Result:');
                $io->definitionList(
                    ['Quote ID' => $quoteId],
                    ['Status' => 'Deleted'],
                    ['Deleted by' => $adminUserId ? "Admin User $adminUserId" : 'System'],
                    ['Timestamp' => date('Y-m-d H:i:s')]
                );
            } else {
                $io->error('Failed to delete quote for unknown reason.');
                return Cli::RETURN_FAILURE;
            }

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to delete immutable quote:');
            $io->block($e->getMessage(), null, 'fg=white;bg=red', ' ERROR ', true);
            
            if ($output->isVerbose()) {
                $io->section('Stack trace:');
                $io->text($e->getTraceAsString());
            }
            
            return Cli::RETURN_FAILURE;
        }
    }
}