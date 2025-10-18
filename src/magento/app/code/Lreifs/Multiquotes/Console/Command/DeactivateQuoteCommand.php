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
 * CLI Command to deactivate immutable quotes
 */
class DeactivateQuoteCommand extends Command
{
    private const ARGUMENT_QUOTE_ID = 'quote-id';
    private const OPTION_ADMIN_USER_ID = 'admin-user-id';

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
        $this->setName('lreifs:multiquotes:deactivate')
            ->setDescription('Deactivate an immutable quote')
            ->addArgument(
                self::ARGUMENT_QUOTE_ID,
                InputArgument::REQUIRED,
                'Quote ID to deactivate'
            )
            ->addOption(
                self::OPTION_ADMIN_USER_ID,
                'a',
                InputOption::VALUE_OPTIONAL,
                'Admin user ID performing the deactivation'
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

            // Validate inputs
            if ($quoteId <= 0) {
                $io->error('Quote ID must be a positive integer');
                return Cli::RETURN_FAILURE;
            }

            $io->title('Deactivating Immutable Quote');
            $io->section('Request Details:');
            $io->definitionList(
                ['Quote ID' => $quoteId],
                ['Admin User ID' => $adminUserId ?? 'System']
            );

            // Deactivate the quote
            $io->text('Processing deactivation...');
            $success = $this->quoteExtensionManagement->deactivateQuote($quoteId, $adminUserId);

            if ($success) {
                // Display success result
                $io->success("Quote $quoteId deactivated successfully!");
                $io->section('Result:');
                $io->definitionList(
                    ['Quote ID' => $quoteId],
                    ['Status' => 'Deactivated'],
                    ['Action performed by' => $adminUserId ? "Admin User $adminUserId" : 'System'],
                    ['Timestamp' => date('Y-m-d H:i:s')],
                    ['Effect' => 'Quote is no longer the active cart for the customer']
                );

                $io->note([
                    'The quote has been deactivated and is no longer the customer\'s active cart.',
                    'The customer will need to activate another quote or create a new one for checkout.',
                    'This action has been logged for audit purposes.'
                ]);
            } else {
                $io->error('Failed to deactivate quote for unknown reason.');
                return Cli::RETURN_FAILURE;
            }

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to deactivate immutable quote:');
            $io->block($e->getMessage(), null, 'fg=white;bg=red', ' ERROR ', true);
            
            if ($output->isVerbose()) {
                $io->section('Stack trace:');
                $io->text($e->getTraceAsString());
            }
            
            return Cli::RETURN_FAILURE;
        }
    }
}