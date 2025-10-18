<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Console\Command;

use Lreifs\Multiquotes\Api\QuoteExtensionManagementInterface;
use Lreifs\Multiquotes\Model\Data\CreateImmutableQuoteRequestFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Magento\Framework\Console\Cli;

/**
 * Console command to create immutable quotes
 * Uses the same service layer as the REST API
 */
class CreateImmutableQuoteCommand extends Command
{
    private const ARGUMENT_QUOTE_ID = 'quote-id';
    private const ARGUMENT_CUSTOMER_ID = 'customer-id';
    private const OPTION_ADMIN_USER_ID = 'admin-user-id';
    private const OPTION_CUSTOMER_REFERENCE = 'customer-reference';
    private const OPTION_CUSTOM_FEE = 'custom-fee';
    private const OPTION_METADATA = 'metadata';

    public function __construct(
        private readonly QuoteExtensionManagementInterface $quoteExtensionManagement,
        private readonly CreateImmutableQuoteRequestFactory $requestFactory,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('lreifs:multiquotes:create-immutable')
            ->setDescription('Create an immutable quote')
            ->addArgument(
                self::ARGUMENT_QUOTE_ID,
                InputArgument::OPTIONAL,
                'Quote ID to make immutable (optional if --items is provided)'
            )
            ->addArgument(
                self::ARGUMENT_CUSTOMER_ID,
                InputArgument::OPTIONAL,
                'Customer ID who owns the quote (optional if --customer-email is provided)'
            )
            ->addOption(
                'customer-email',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Customer email (alternative to customer ID)'
            )
            ->addOption(
                self::OPTION_ADMIN_USER_ID,
                'a',
                InputOption::VALUE_OPTIONAL,
                'Admin user ID creating the immutable quote'
            )
            ->addOption(
                self::OPTION_CUSTOMER_REFERENCE,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Customer reference (PO number, etc.)'
            )
            ->addOption(
                self::OPTION_CUSTOM_FEE,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Custom fee to apply to the quote'
            )
            ->addOption(
                self::OPTION_METADATA,
                'm',
                InputOption::VALUE_OPTIONAL,
                'Additional metadata as JSON string'
            )
            ->addOption(
                'items',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Items to add to new quote in JSON format: [{"sku":"product-1","qty":2,"price":99.99}]'
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
            $quoteId = $input->getArgument(self::ARGUMENT_QUOTE_ID) ? 
                (int)$input->getArgument(self::ARGUMENT_QUOTE_ID) : 0;
            $customerId = $input->getArgument(self::ARGUMENT_CUSTOMER_ID) ? 
                (int)$input->getArgument(self::ARGUMENT_CUSTOMER_ID) : 0;
            $customerEmail = $input->getOption('customer-email');
            $itemsJson = $input->getOption('items');
            $adminUserId = $input->getOption(self::OPTION_ADMIN_USER_ID) ? 
                (int)$input->getOption(self::OPTION_ADMIN_USER_ID) : null;
            $customerReference = $input->getOption(self::OPTION_CUSTOMER_REFERENCE);
            $customFee = $input->getOption(self::OPTION_CUSTOM_FEE) ? 
                (float)$input->getOption(self::OPTION_CUSTOM_FEE) : null;
            $metadataJson = $input->getOption(self::OPTION_METADATA);

            // Determine creation mode
            $createFromItems = !empty($itemsJson);
            $createFromQuote = $quoteId > 0;
            
            // Validate inputs
            if (!$createFromItems && !$createFromQuote) {
                $io->error('Either Quote ID or Items must be provided');
                return Cli::RETURN_FAILURE;
            }

            // Validate customer identification
            if ($customerId <= 0 && empty($customerEmail)) {
                $io->error('Either Customer ID or Customer Email must be provided');
                return Cli::RETURN_FAILURE;
            }

            // Parse metadata
            $metadata = [];
            if ($metadataJson) {
                $metadata = json_decode($metadataJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $io->error('Invalid JSON format for metadata: ' . json_last_error_msg());
                    return Cli::RETURN_FAILURE;
                }
            }

            // Parse items if provided
            $items = [];
            if ($itemsJson) {
                $items = json_decode($itemsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $io->error('Invalid JSON format for items: ' . json_last_error_msg());
                    return Cli::RETURN_FAILURE;
                }
                
                // Validate items structure
                foreach ($items as $index => $item) {
                    if (!isset($item['sku']) || !isset($item['qty'])) {
                        $io->error("Item at index $index is missing required fields 'sku' or 'qty'");
                        return Cli::RETURN_FAILURE;
                    }
                }
            }

            // Create request object
            $requestData = [
                'admin_user_id' => $adminUserId,
                'customer_reference' => $customerReference,
                'custom_fee' => $customFee,
                'metadata' => $metadata,
                'ip_address' => 'console',
                'user_agent' => 'Magento CLI'
            ];
            
            // Add quote_id only if creating from existing quote
            if ($createFromQuote) {
                $requestData['quote_id'] = $quoteId;
            }
            
            // Add items only if creating from items
            if ($createFromItems) {
                $requestData['items'] = $items;
            }
            
            // Add customer identification (ID or email)
            if ($customerId > 0) {
                $requestData['customer_id'] = $customerId;
            }
            if (!empty($customerEmail)) {
                $requestData['customer_email'] = $customerEmail;
            }
            
            $request = $this->requestFactory->create(['data' => $requestData]);

            $io->title('Creating Immutable Quote');
            $io->section('Request Details:');
            $customerDisplay = [];
            if ($customerId > 0) {
                $customerDisplay['Customer ID'] = $customerId;
            }
            if (!empty($customerEmail)) {
                $customerDisplay['Customer Email'] = $customerEmail;
            }
            
            $displayData = [];
            
            if ($createFromQuote) {
                $displayData['Quote ID'] = $quoteId;
                $displayData['Mode'] = 'From existing quote';
            } else {
                $displayData['Mode'] = 'Create new quote with items';
                $displayData['Items count'] = count($items);
            }
            
            $displayData = array_merge($displayData, $customerDisplay);
            $displayData['Admin User ID'] = $adminUserId ?? 'Not specified';
            $displayData['Customer Reference'] = $customerReference ?? 'Not specified';
            $displayData['Custom Fee'] = $customFee ?? 'Not specified';
            $displayData['Metadata'] = $metadataJson ?? 'None';
            
            $io->definitionList($displayData);
            
            // Show items details if creating from items
            if ($createFromItems && !empty($items)) {
                $io->section('Items to add:');
                $rows = [];
                foreach ($items as $item) {
                    $rows[] = [
                        $item['sku'],
                        $item['qty'],
                        $item['price'] ?? 'Auto'
                    ];
                }
                $io->table(['SKU', 'Quantity', 'Price'], $rows);
            }

            // Use the same service as the API
            $io->text('Processing...');
            $result = $this->quoteExtensionManagement->createImmutableQuoteWithItems($request);

            // Display success result
            $io->success('Immutable quote created successfully!');
            $io->section('Result:');
            $io->definitionList(
                ['Extension ID' => $result->getEntityId()],
                ['Quote ID' => $result->getQuoteId()],
                ['Is Immutable' => $result->isImmutable() ? 'Yes' : 'No'],
                ['Customer Reference' => $result->getCustomerReference() ?? 'None'],
                ['Custom Fee' => $result->getCustomFee()],
                ['Created At' => $result->getCreatedAt()],
                ['Immutable Created At' => $result->getImmutableCreatedAt()],
                ['Immutable Created By' => $result->getImmutableCreatedBy() ?? 'System']
            );

            if (!empty($result->getMetadata())) {
                $io->section('Metadata:');
                $io->text(json_encode($result->getMetadata(), JSON_PRETTY_PRINT));
            }

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Failed to create immutable quote:',
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