<?php
/**
 * Lreifs Multiquotes Expire Quotes Cron Job
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

namespace Lreifs\Multiquotes\Cron;

use Lreifs\Multiquotes\Service\QuoteExpirationService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class ExpireQuotes
{
    const CONFIG_PATH_ENABLED = 'lreifs_multiquotes/expiration/cron_enabled';
    const CONFIG_PATH_BATCH_SIZE = 'lreifs_multiquotes/expiration/batch_size';
    const CONFIG_PATH_MAX_EXECUTION_TIME = 'lreifs_multiquotes/expiration/max_execution_time';
    
    /**
     * @var QuoteExpirationService
     */
    private $expirationService;
    
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param QuoteExpirationService $expirationService
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteExpirationService $expirationService,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->expirationService = $expirationService;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }
    
    /**
     * Execute cron job to process expired quotes
     *
     * @return void
     */
    public function execute(): void
    {
        // Check if cron is enabled
        if (!$this->isCronEnabled()) {
            $this->logger->debug('Expired quotes cron job is disabled');
            return;
        }
        
        $startTime = microtime(true);
        $batchSize = $this->getBatchSize();
        $maxExecutionTime = $this->getMaxExecutionTime();
        
        $this->logger->info('Starting expired quotes cron job', [
            'batch_size' => $batchSize,
            'max_execution_time' => $maxExecutionTime
        ]);
        
        try {
            $totalProcessed = 0;
            $totalFailed = 0;
            $iterations = 0;
            $maxIterations = 10; // Safety limit to prevent infinite loops
            
            do {
                $iterations++;
                
                // Check execution time limit
                $currentExecutionTime = microtime(true) - $startTime;
                if ($currentExecutionTime > $maxExecutionTime) {
                    $this->logger->warning('Expired quotes cron job stopped due to execution time limit', [
                        'execution_time' => $currentExecutionTime,
                        'max_execution_time' => $maxExecutionTime,
                        'total_processed' => $totalProcessed,
                        'iterations' => $iterations
                    ]);
                    break;
                }
                
                // Process batch of expired quotes
                $result = $this->expirationService->processExpiredQuotes($batchSize, false);
                
                $totalProcessed += $result['processed'];
                $totalFailed += $result['failed'];
                
                $this->logger->debug('Processed expired quotes batch', [
                    'iteration' => $iterations,
                    'batch_found' => $result['found'],
                    'batch_processed' => $result['processed'],
                    'batch_failed' => $result['failed'],
                    'total_processed' => $totalProcessed,
                    'execution_time' => microtime(true) - $startTime
                ]);
                
                // Continue if we found quotes to process and haven't hit limits
                $shouldContinue = $result['found'] > 0 && 
                                $iterations < $maxIterations &&
                                $currentExecutionTime < $maxExecutionTime;
                
            } while ($shouldContinue);
            
            $totalExecutionTime = microtime(true) - $startTime;
            
            $this->logger->info('Expired quotes cron job completed', [
                'total_processed' => $totalProcessed,
                'total_failed' => $totalFailed,
                'iterations' => $iterations,
                'execution_time' => $totalExecutionTime,
                'batch_size' => $batchSize
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Expired quotes cron job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time' => microtime(true) - $startTime
            ]);
        }
    }
    
    /**
     * Check if cron is enabled
     *
     * @return bool
     */
    private function isCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Get batch size from configuration
     *
     * @return int
     */
    private function getBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_PATH_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE
        ) ?: 100;
    }
    
    /**
     * Get max execution time from configuration
     *
     * @return int
     */
    private function getMaxExecutionTime(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::CONFIG_PATH_MAX_EXECUTION_TIME,
            ScopeInterface::SCOPE_STORE
        ) ?: 300; // 5 minutes default
    }
}