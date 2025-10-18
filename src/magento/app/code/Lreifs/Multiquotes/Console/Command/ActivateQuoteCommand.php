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
 * Console command to activate immutable quotes
 * Uses the same service layer as the REST API
 */
class ActivateQuoteCommand extends Command
{
    private const ARGUMENT_QUOTE_ID = 'quote-id';
    private const OPTION_ADMIN_USER_ID = 'admin-user-id';

    public function __construct(
        private readonly QuoteExtensionManagementInterface $quoteExtensionManagement,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('lreifs:multiquotes:activate')
            ->setDescription('Activate an immutable quote')
            ->addArgument(
                self::ARGUMENT_QUOTE_ID,
                InputArgument::REQUIRED,
                'Quote ID to activate'
            )
            ->addOption(
                self::OPTION_ADMIN_USER_ID,
                'a',
                InputOption::VALUE_OPTIONAL,
                'Admin user ID performing the activation'
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
            $quoteId = (int)$input->getArgument(self::ARGUMENT_QUOTE_ID);
            $adminUserId = $input->getOption(self::OPTION_ADMIN_USER_ID) ? 
                (int)$input->getOption(self::OPTION_ADMIN_USER_ID) : null;

            if ($quoteId <= 0) {
                $io->error('Quote ID must be a positive integer');
                return Cli::RETURN_FAILURE;
            }

            $io->title('Activating Immutable Quote');
            $io->text("Quote ID: {$quoteId}");
            $io->text("Admin User ID: " . ($adminUserId ?? 'System'));

            // Use the same service as the API
            $result = $this->quoteExtensionManagement->activateQuote($quoteId, $adminUserId);

            if ($result) {
                $io->success("Quote {$quoteId} activated successfully!");
            } else {
                $io->warning("Quote {$quoteId} activation returned false");
                return Cli::RETURN_FAILURE;
            }

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Failed to activate quote:',
                $e->getMessage()
            ]);

            if ($output->isVeryVerbose()) {
                $io->text('Stack trace:');
                $io->text($e->getTraceAsString());
            }

            return Cli::RETURN_FAILURE;
        }
    }
}