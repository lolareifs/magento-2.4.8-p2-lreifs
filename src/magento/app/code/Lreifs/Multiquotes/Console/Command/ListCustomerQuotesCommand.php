<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Console\Command;

use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User\CollectionFactory as AdminUserCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Magento\Framework\Console\Cli;

/**
 * Console command to list customer's immutable quotes
 * Uses the same service layer as the REST API
 */
class ListCustomerQuotesCommand extends Command
{
    private const ARGUMENT_CUSTOMER_ID = 'customer-id';
    private const OPTION_IMMUTABLE_ONLY = 'immutable-only';
    private const OPTION_FORMAT = 'format';

    public function __construct(
        private readonly QuoteExtensionManagementInterface $quoteExtensionManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly AdminUserCollectionFactory $adminUserCollectionFactory,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('lreifs:multiquotes:list-customer-quotes')
            ->setDescription('List quotes for a specific customer or all quotes if no customer ID provided')
            ->addArgument(
                self::ARGUMENT_CUSTOMER_ID,
                InputArgument::OPTIONAL,
                'Customer ID to list quotes for (optional - if not provided, shows all quotes)'
            )
            ->addOption(
                self::OPTION_IMMUTABLE_ONLY,
                'i',
                InputOption::VALUE_NONE,
                'Show only immutable quotes'
            )
            ->addOption(
                self::OPTION_FORMAT,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format (table, json, csv)',
                'table'
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
            $customerIdInput = $input->getArgument(self::ARGUMENT_CUSTOMER_ID);
            $customerId = $customerIdInput ? (int)$customerIdInput : null;
            $immutableOnly = $input->getOption(self::OPTION_IMMUTABLE_ONLY);
            $format = $input->getOption(self::OPTION_FORMAT);

            // Validate customer ID if provided
            if ($customerIdInput && $customerId <= 0) {
                $io->error('Customer ID must be a positive integer');
                return Cli::RETURN_FAILURE;
            }

            if (!in_array($format, ['table', 'json', 'csv'])) {
                $io->error('Format must be one of: table, json, csv');
                return Cli::RETURN_FAILURE;
            }

            $io->title('Quote Extensions List');
            
            if ($customerId) {
                $io->text("Customer ID: {$customerId}");
                $io->text("Filter: " . ($immutableOnly ? 'Immutable only' : 'All quotes for this customer'));
                
                // Use the customer-specific service method
                $quotes = $this->quoteExtensionManagement->getCustomerQuotes($customerId, $immutableOnly);
                
                if (empty($quotes)) {
                    $io->info('No quotes found for this customer.');
                    return Cli::RETURN_SUCCESS;
                }
                
                $this->displayQuotes($quotes, $format, $io, $output);
                $io->success(sprintf('Found %d quote(s) for customer %d', count($quotes), $customerId));
                
            } else {
                $io->text("Scope: All customers");
                $io->text("Filter: " . ($immutableOnly ? 'Immutable quotes only' : 'All quotes in system'));
                
                // Use the getAllImmutableQuotes method for system-wide listing
                $filters = $immutableOnly ? ['is_immutable' => 1] : [];
                $result = $this->quoteExtensionManagement->getAllImmutableQuotes(50, 1, $filters);
                
                if (empty($result['quotes'])) {
                    $io->info('No quotes found in the system.');
                    return Cli::RETURN_SUCCESS;
                }
                
                // Convert the result format to match expected format
                $this->displaySystemQuotes($result['quotes'], $format, $io, $output);
                $io->success(sprintf('Found %d quote(s) in total (showing first 50)', $result['total_count']));
                
                if ($result['total_count'] > 50) {
                    $io->note(sprintf('Total quotes in system: %d (showing first 50)', $result['total_count']));
                }
            }

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Failed to list customer quotes:',
                $e->getMessage()
            ]);

            if ($output->isVeryVerbose()) {
                $io->text('Stack trace:');
                $io->text($e->getTraceAsString());
            }

            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Display quotes in different formats
     */
    private function displayQuotes(array $quotes, string $format, SymfonyStyle $io, OutputInterface $output): void
    {
        switch ($format) {
            case 'json':
                $this->displayAsJson($quotes, $output);
                break;
            case 'csv':
                $this->displayAsCsv($quotes, $output);
                break;
            case 'table':
            default:
                $this->displayAsTable($quotes, $io);
                break;
        }
    }

    /**
     * Display as table
     */
    private function displayAsTable(array $quotes, SymfonyStyle $io): void
    {
        $headers = [
            'Extension ID',
            'Quote ID',
            'Is Active',
            'Immutable',
            'Expired',
            'Terms Accepted',
            'Customer Email',
            'Custom Fee',
            'Created At',
            'Created By Email'
        ];

        $rows = [];
        foreach ($quotes as $quote) {
            // Get customer email and is_active status from the associated quote
            $customerEmail = '-';
            $isActive = '-';
            try {
                $cartQuote = $this->cartRepository->get($quote->getQuoteId());
                $customerEmail = $cartQuote->getCustomerEmail() ?? '-';
                $isActive = $cartQuote->getIsActive() ? 'Yes' : 'No';
            } catch (\Exception $e) {
                $customerEmail = '-';
                $isActive = '-';
            }
            
            // Get admin user email who created the immutable quote
            $createdByEmail = '-';
            if ($quote->getImmutableCreatedBy()) {
                try {
                    $adminCollection = $this->adminUserCollectionFactory->create();
                    $adminCollection->addFieldToFilter('user_id', $quote->getImmutableCreatedBy());
                    $adminUser = $adminCollection->getFirstItem();
                    if ($adminUser && $adminUser->getId()) {
                        $createdByEmail = $adminUser->getEmail() ?? $adminUser->getUsername();
                    }
                } catch (\Exception $e) {
                    $createdByEmail = 'Admin ID: ' . $quote->getImmutableCreatedBy();
                }
            }
            
            $rows[] = [
                $quote->getEntityId(),
                $quote->getQuoteId(),
                $isActive,
                $quote->isImmutable() ? 'Yes' : 'No',
                $quote->isExpired() ? 'Yes' : 'No',
                $quote->getTermsAccepted() ? 'Yes' : 'No',
                $customerEmail,
                number_format($quote->getCustomFee(), 2),
                $quote->getCreatedAt(),
                $createdByEmail
            ];
        }

        $io->table($headers, $rows);
    }

    /**
     * Display as JSON
     */
    private function displayAsJson(array $quotes, OutputInterface $output): void
    {
        $data = [];
        foreach ($quotes as $quote) {
            $data[] = [
                'entity_id' => $quote->getEntityId(),
                'quote_id' => $quote->getQuoteId(),
                'is_immutable' => $quote->isImmutable(),
                'is_expired' => $quote->isExpired(),
                'terms_accepted' => $quote->getTermsAccepted(),
                'customer_reference' => $quote->getCustomerReference(),
                'custom_fee' => $quote->getCustomFee(),
                'sorting_order' => $quote->getSortingOrder(),
                'metadata' => $quote->getMetadata(),
                'immutable_created_by' => $quote->getImmutableCreatedBy(),
                'immutable_created_at' => $quote->getImmutableCreatedAt(),
                'created_at' => $quote->getCreatedAt(),
                'updated_at' => $quote->getUpdatedAt()
            ];
        }

        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Display as CSV
     */
    private function displayAsCsv(array $quotes, OutputInterface $output): void
    {
        $headers = [
            'extension_id',
            'quote_id',
            'is_immutable',
            'is_expired',
            'terms_accepted',
            'customer_reference',
            'custom_fee',
            'sorting_order',
            'immutable_created_by',
            'immutable_created_at',
            'created_at',
            'updated_at'
        ];

        // Output CSV headers
        $output->writeln(implode(',', $headers));

        // Output CSV rows
        foreach ($quotes as $quote) {
            $row = [
                $quote->getEntityId(),
                $quote->getQuoteId(),
                $quote->isImmutable() ? '1' : '0',
                $quote->isExpired() ? '1' : '0',
                $quote->getTermsAccepted() ? '1' : '0',
                '"' . str_replace('"', '""', $quote->getCustomerReference() ?? '') . '"',
                $quote->getCustomFee(),
                $quote->getSortingOrder(),
                $quote->getImmutableCreatedBy() ?? '',
                $quote->getImmutableCreatedAt() ?? '',
                $quote->getCreatedAt() ?? '',
                $quote->getUpdatedAt() ?? ''
            ];

            $output->writeln(implode(',', $row));
        }
    }
    
    /**
     * Display system quotes (from getAllImmutableQuotes)
     */
    private function displaySystemQuotes(array $quotes, string $format, SymfonyStyle $io, OutputInterface $output): void
    {
        switch ($format) {
            case 'json':
                $this->displaySystemQuotesAsJson($quotes, $output);
                break;
            case 'csv':
                $this->displaySystemQuotesAsCsv($quotes, $output);
                break;
            default:
                $this->displaySystemQuotesAsTable($quotes, $io);
        }
    }
    
    /**
     * Display system quotes as table
     */
    private function displaySystemQuotesAsTable(array $quotes, SymfonyStyle $io): void
    {
        $headers = [
            'Quote ID',
            'Customer ID',
            'Is Active',
            'Is Immutable',
            'Customer Email',
            'Items Count',
            'Grand Total',
            'Currency',
            'Created At',
            'Updated At'
        ];

        $rows = [];
        foreach ($quotes as $quote) {
            $rows[] = [
                $quote['quote_id'],
                $quote['customer_id'] ?? '-',
                $quote['is_active'] ? 'Yes' : 'No',
                $quote['is_immutable'] ? 'Yes' : 'No',
                $quote['customer_email'] ?? '-',
                $quote['items_count'] ?? '0',
                number_format((float)($quote['grand_total'] ?? 0), 2),
                $quote['currency_code'] ?? '-',
                $quote['created_at'] ?? '-',
                $quote['updated_at'] ?? '-'
            ];
        }

        $io->table($headers, $rows);
    }
    
    /**
     * Display system quotes as JSON
     */
    private function displaySystemQuotesAsJson(array $quotes, OutputInterface $output): void
    {
        $output->writeln(json_encode($quotes, JSON_PRETTY_PRINT));
    }
    
    /**
     * Display system quotes as CSV
     */
    private function displaySystemQuotesAsCsv(array $quotes, OutputInterface $output): void
    {
        $headers = [
            'quote_id',
            'customer_id',
            'is_active',
            'is_immutable',
            'customer_email',
            'items_count',
            'grand_total',
            'currency_code',
            'created_at',
            'updated_at'
        ];

        // Output CSV headers
        $output->writeln(implode(',', $headers));

        // Output CSV rows
        foreach ($quotes as $quote) {
            $row = [
                $quote['quote_id'],
                $quote['customer_id'] ?? '',
                $quote['is_active'] ? '1' : '0',
                $quote['is_immutable'] ? '1' : '0',
                $quote['customer_email'] ?? '',
                $quote['items_count'] ?? '0',
                $quote['grand_total'] ?? '0',
                $quote['currency_code'] ?? '',
                $quote['created_at'] ?? '',
                $quote['updated_at'] ?? ''
            ];

            $output->writeln(implode(',', $row));
        }
    }
    
    /**
     * Convert system quotes format to QuoteExtension-like format for display
     */
    private function convertSystemQuotesToExtensionFormat(array $systemQuotes): array
    {
        $quotes = [];
        
        foreach ($systemQuotes as $quoteData) {
            // Create a mock object with the data we need for display
            $mockQuote = new class($quoteData) {
                private array $data;
                
                public function __construct(array $data)
                {
                    $this->data = $data;
                }
                
                public function getEntityId() { return $this->data['quote_id']; }
                public function getQuoteId() { return $this->data['quote_id']; }
                public function isImmutable() { return $this->data['is_immutable']; }
                public function isExpired() { return false; } // From system quotes, assume not expired
                public function getTermsAccepted() { return false; } // Not available in system format
                public function getCustomFee() { return 0.0; } // Not available in system format
                public function getCreatedAt() { return $this->data['created_at']; }
                public function getImmutableCreatedBy() { return null; } // Not available in system format
                public function getCustomerReference() { return null; }
                public function getUpdatedAt() { return $this->data['updated_at']; }
            };
            
            $quotes[] = $mockQuote;
        }
        
        return $quotes;
    }
}