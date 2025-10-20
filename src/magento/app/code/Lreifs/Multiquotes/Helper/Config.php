<?php
/**
 * Lreifs Multiquotes Configuration Helper
 * 
 * Optimized helper class for managing module configurations with caching
 * and performance optimizations.
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Config extends AbstractHelper
{
    // Configuration paths matching system.xml/config.xml
    public const XML_PATH_ENABLED = 'multiquotes/general/enabled';
    public const XML_PATH_RATE_LIMITING_ENABLED = 'multiquotes/rate_limiting/enabled';
    public const XML_PATH_REQUESTS_PER_HOUR = 'multiquotes/rate_limiting/requests_per_hour';
    public const XML_PATH_CRON_ENABLED = 'lreifs_multiquotes/expiration/cron_enabled';
    public const XML_PATH_BATCH_SIZE = 'lreifs_multiquotes/expiration/batch_size';
    public const XML_PATH_MAX_EXECUTION_TIME = 'lreifs_multiquotes/expiration/max_execution_time';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    /**
     * Get configuration value (minimal, no cache)
     */
    private function getConfigValue(string $path, ?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if rate limiting is enabled
     */
    public function isRateLimitingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RATE_LIMITING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get requests per hour limit
     */
    public function getRequestsPerHour(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_REQUESTS_PER_HOUR, $storeId) ?: 100;
    }

    /**
     * Check if cron is enabled for quote expiration
     */
    public function isCronEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CRON_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get batch size for quote expiration
     */
    public function getBatchSize(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_BATCH_SIZE, $storeId) ?: 100;
    }

    /**
     * Get max execution time for quote expiration
     */
    public function getMaxExecutionTime(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_MAX_EXECUTION_TIME, $storeId) ?: 300;
    }
}