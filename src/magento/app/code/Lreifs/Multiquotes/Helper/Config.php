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
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Config extends AbstractHelper
{
    /**
     * Configuration paths
     */
    public const XML_PATH_ENABLED = 'multiquotes/general/enabled';
    public const XML_PATH_DEBUG_MODE = 'multiquotes/general/debug_mode';
    public const XML_PATH_CACHE_ENABLED = 'multiquotes/general/cache_enabled';
    
    // Rate Limiting
    public const XML_PATH_RATE_LIMITING_ENABLED = 'multiquotes/rate_limiting/enabled';
    public const XML_PATH_REQUESTS_PER_HOUR = 'multiquotes/rate_limiting/requests_per_hour';
    public const XML_PATH_REQUESTS_PER_MINUTE = 'multiquotes/rate_limiting/requests_per_minute';
    public const XML_PATH_BURST_LIMIT = 'multiquotes/rate_limiting/burst_limit';
    public const XML_PATH_BLOCK_DURATION = 'multiquotes/rate_limiting/block_duration';
    public const XML_PATH_WHITELIST_IPS = 'multiquotes/rate_limiting/whitelist_ips';
    
    // Quote Settings - Removed non-implemented configurations
    
    // Performance Settings - Removed non-implemented configurations
    
    // Advanced Settings - Removed non-implemented configurations
    
    /**
     * Cache configuration
     */
    private const CACHE_TAG = 'MULTIQUOTES_CONFIG';
    private const CACHE_LIFETIME = 3600; // 1 hour
    
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;
    
    /**
     * @var Pool
     */
    private $cacheFrontendPool;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array
     */
    private $configCache = [];

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->logger = $logger;
    }

    /**
     * Get configuration value with caching
     */
    private function getConfigValue(string $path, ?string $scope = null, ?int $scopeId = null): ?string
    {
        $cacheKey = sprintf('%s_%s_%s_%s', self::CACHE_TAG, $path, $scope ?: 'default', $scopeId ?: 0);
        
        // Check in-memory cache first
        if (isset($this->configCache[$cacheKey])) {
            return $this->configCache[$cacheKey];
        }
        
        // Check Magento cache if enabled
        if ($this->isCacheEnabled()) {
            $cache = $this->cacheFrontendPool->get('config');
            $cachedValue = $cache->load($cacheKey);
            if ($cachedValue !== false) {
                $this->configCache[$cacheKey] = $cachedValue;
                return $cachedValue;
            }
        }
        
        // Get from configuration
        $value = $this->scopeConfig->getValue(
            $path,
            $scope ?: ScopeInterface::SCOPE_STORE,
            $scopeId
        );
        
        // Store in cache
        $this->configCache[$cacheKey] = $value;
        
        if ($this->isCacheEnabled()) {
            $cache = $this->cacheFrontendPool->get('config');
            $cache->save($value, $cacheKey, [self::CACHE_TAG], self::CACHE_LIFETIME);
        }
        
        return $value;
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
     * Check if debug mode is enabled
     */
    public function isDebugMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if caching is enabled
     */
    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CACHE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // Rate Limiting Configuration Methods
    
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
        return (int) $this->getConfigValue(self::XML_PATH_REQUESTS_PER_HOUR, ScopeInterface::SCOPE_STORE, $storeId) ?: 100;
    }

    /**
     * Get requests per minute limit
     */
    public function getRequestsPerMinute(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_REQUESTS_PER_MINUTE, ScopeInterface::SCOPE_STORE, $storeId) ?: 10;
    }

    /**
     * Get burst limit
     */
    public function getBurstLimit(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_BURST_LIMIT, ScopeInterface::SCOPE_STORE, $storeId) ?: 5;
    }

    /**
     * Get block duration in minutes
     */
    public function getBlockDuration(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_BLOCK_DURATION, ScopeInterface::SCOPE_STORE, $storeId) ?: 60;
    }

    /**
     * Get whitelisted IP addresses
     */
    public function getWhitelistIps(?int $storeId = null): array
    {
        $ips = $this->getConfigValue(self::XML_PATH_WHITELIST_IPS, ScopeInterface::SCOPE_STORE, $storeId);
        return $ips ? array_filter(array_map('trim', explode("\n", $ips))) : [];
    }

    // Quote Configuration Methods
    
    // Removed non-implemented configuration methods

    // Performance Configuration Methods
    
    /**
     * Get cache lifetime
     */
    public function getCacheLifetime(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_CACHE_LIFETIME, ScopeInterface::SCOPE_STORE, $storeId) ?: 3600;
    }

    /**
     * Get batch size
     */
    public function getBatchSize(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_BATCH_SIZE, ScopeInterface::SCOPE_STORE, $storeId) ?: 50;
    }

    /**
     * Check if async processing is enabled
     */
    public function isAsyncProcessingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_ASYNC_PROCESSING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if cleanup of expired quotes is enabled
     */
    public function isCleanupExpiredQuotes(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CLEANUP_EXPIRED_QUOTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cleanup days
     */
    public function getCleanupDays(?int $storeId = null): int
    {
        return (int) $this->getConfigValue(self::XML_PATH_CLEANUP_DAYS, ScopeInterface::SCOPE_STORE, $storeId) ?: 30;
    }

    // Removed non-implemented advanced configuration methods

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        $this->configCache = [];
        
        if ($this->isCacheEnabled()) {
            $cache = $this->cacheFrontendPool->get('config');
            $cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [self::CACHE_TAG]);
        }
        
        $this->cacheTypeList->cleanType('config');
    }

    /**
     * Get all rate limiting configuration
     */
    public function getRateLimitingConfig(?int $storeId = null): array
    {
        return [
            'enabled' => $this->isRateLimitingEnabled($storeId),
            'requests_per_hour' => $this->getRequestsPerHour($storeId),
            'requests_per_minute' => $this->getRequestsPerMinute($storeId),
            'burst_limit' => $this->getBurstLimit($storeId),
            'block_duration' => $this->getBlockDuration($storeId),
            'whitelist_ips' => $this->getWhitelistIps($storeId)
        ];
    }
}