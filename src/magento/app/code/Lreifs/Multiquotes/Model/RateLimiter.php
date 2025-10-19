<?php
/**
 * Lreifs Multiquotes Rate Limiter
 * 
 * High-performance rate limiting implementation with Redis/Cache support
 * and multiple limiting strategies (per hour, per minute, burst).
 * 
 * @category    Lreifs
 * @package     Lreifs_Multiquotes
 * @author      Lola Reifs <lola@reifs.com>
 * @copyright   2025 Lreifs
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Lreifs\Multiquotes\Model;

use Lreifs\Multiquotes\Helper\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class RateLimiter
{
    /**
     * Cache keys for rate limiting
     */
    private const CACHE_KEY_HOUR = 'multiquotes_rate_limit_hour_%s';
    private const CACHE_KEY_MINUTE = 'multiquotes_rate_limit_minute_%s';
    private const CACHE_KEY_BURST = 'multiquotes_rate_limit_burst_%s';
    private const CACHE_KEY_BLOCKED = 'multiquotes_blocked_%s';
    
    /**
     * Rate limiting result codes
     */
    public const RESULT_ALLOWED = 'allowed';
    public const RESULT_LIMITED_HOUR = 'limited_hour';
    public const RESULT_LIMITED_MINUTE = 'limited_minute';
    public const RESULT_LIMITED_BURST = 'limited_burst';
    public const RESULT_BLOCKED = 'blocked';
    public const RESULT_DISABLED = 'disabled';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Pool
     */
    private $cacheFrontendPool;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct(
        Config $config,
        Pool $cacheFrontendPool,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Check if request is allowed under rate limiting rules
     */
    public function isAllowed(?int $customerId = null, ?string $ip = null): array
    {
        // Check if rate limiting is enabled
        if (!$this->config->isRateLimitingEnabled()) {
            return [
                'allowed' => true,
                'result' => self::RESULT_DISABLED,
                'message' => 'Rate limiting is disabled'
            ];
        }

        $identifier = $this->getIdentifier($customerId, $ip);
        
        // Check if IP is whitelisted
        if ($this->isWhitelisted($ip)) {
            $this->logDebug("IP {$ip} is whitelisted, allowing request", ['identifier' => $identifier]);
            return [
                'allowed' => true,
                'result' => self::RESULT_ALLOWED,
                'message' => 'IP whitelisted'
            ];
        }

        // Check if currently blocked
        if ($this->isBlocked($identifier)) {
            $this->logDebug("Identifier {$identifier} is currently blocked", ['identifier' => $identifier]);
            return [
                'allowed' => false,
                'result' => self::RESULT_BLOCKED,
                'message' => 'Currently blocked due to rate limit violations',
                'retry_after' => $this->getBlockTimeRemaining($identifier)
            ];
        }

        // Check hourly limit
        $hourlyCheck = $this->checkHourlyLimit($identifier);
        if (!$hourlyCheck['allowed']) {
            $this->handleViolation($identifier, self::RESULT_LIMITED_HOUR);
            return $hourlyCheck;
        }

        // Check per-minute limit
        $minuteCheck = $this->checkMinuteLimit($identifier);
        if (!$minuteCheck['allowed']) {
            $this->handleViolation($identifier, self::RESULT_LIMITED_MINUTE);
            return $minuteCheck;
        }

        // Check burst limit
        $burstCheck = $this->checkBurstLimit($identifier);
        if (!$burstCheck['allowed']) {
            $this->handleViolation($identifier, self::RESULT_LIMITED_BURST);
            return $burstCheck;
        }

        // All checks passed, increment counters
        $this->incrementCounters($identifier);
        
        $this->logDebug("Request allowed for identifier {$identifier}");
        
        return [
            'allowed' => true,
            'result' => self::RESULT_ALLOWED,
            'message' => 'Request allowed',
            'remaining' => [
                'hourly' => $this->getRemainingRequests($identifier, 'hour'),
                'minute' => $this->getRemainingRequests($identifier, 'minute'),
                'burst' => $this->getRemainingRequests($identifier, 'burst')
            ]
        ];
    }

    /**
     * Get unique identifier for rate limiting
     */
    private function getIdentifier(?int $customerId = null, ?string $ip = null): string
    {
        if ($customerId) {
            return "customer_{$customerId}";
        }

        $ip = $ip ?: $this->remoteAddress->getRemoteAddress();
        return "ip_{$ip}";
    }

    /**
     * Check if IP is whitelisted
     */
    private function isWhitelisted(?string $ip = null): bool
    {
        $ip = $ip ?: $this->remoteAddress->getRemoteAddress();
        $whitelist = $this->config->getWhitelistIps();
        
        foreach ($whitelist as $whitelistedIp) {
            if ($this->matchesIpPattern($ip, $whitelistedIp)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if IP matches pattern (supports CIDR notation)
     */
    private function matchesIpPattern(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }

        // CIDR notation support
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }

        // Wildcard support (basic)
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $ip);
        }

        return false;
    }

    /**
     * Check if identifier is currently blocked
     */
    private function isBlocked(string $identifier): bool
    {
        $cache = $this->cacheFrontendPool->get('default');
        $cacheKey = sprintf(self::CACHE_KEY_BLOCKED, $identifier);
        
        return $cache->load($cacheKey) !== false;
    }

    /**
     * Get remaining block time in seconds
     */
    private function getBlockTimeRemaining(string $identifier): int
    {
        $cache = $this->cacheFrontendPool->get('default');
        $cacheKey = sprintf(self::CACHE_KEY_BLOCKED, $identifier);
        
        $blockData = $cache->load($cacheKey);
        if (!$blockData) {
            return 0;
        }
        
        $blockInfo = json_decode($blockData, true);
        return max(0, $blockInfo['expires_at'] - time());
    }

    /**
     * Check hourly rate limit
     */
    private function checkHourlyLimit(string $identifier): array
    {
        $limit = $this->config->getRequestsPerHour();
        $current = $this->getCurrentCount($identifier, 'hour');
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'result' => self::RESULT_LIMITED_HOUR,
                'message' => 'Hourly rate limit exceeded',
                'limit' => $limit,
                'current' => $current,
                'retry_after' => $this->getResetTime('hour')
            ];
        }
        
        return ['allowed' => true];
    }

    /**
     * Check per-minute rate limit
     */
    private function checkMinuteLimit(string $identifier): array
    {
        $limit = $this->config->getRequestsPerMinute();
        $current = $this->getCurrentCount($identifier, 'minute');
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'result' => self::RESULT_LIMITED_MINUTE,
                'message' => 'Per-minute rate limit exceeded',
                'limit' => $limit,
                'current' => $current,
                'retry_after' => $this->getResetTime('minute')
            ];
        }
        
        return ['allowed' => true];
    }

    /**
     * Check burst limit
     */
    private function checkBurstLimit(string $identifier): array
    {
        $limit = $this->config->getBurstLimit();
        $current = $this->getCurrentCount($identifier, 'burst');
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'result' => self::RESULT_LIMITED_BURST,
                'message' => 'Burst rate limit exceeded',
                'limit' => $limit,
                'current' => $current,
                'retry_after' => $this->getResetTime('burst')
            ];
        }
        
        return ['allowed' => true];
    }

    /**
     * Get current request count for time window
     */
    private function getCurrentCount(string $identifier, string $window): int
    {
        $cache = $this->cacheFrontendPool->get('default');
        $cacheKey = $this->getCacheKey($identifier, $window);
        
        $count = $cache->load($cacheKey);
        return $count !== false ? (int) $count : 0;
    }

    /**
     * Get remaining requests for time window
     */
    private function getRemainingRequests(string $identifier, string $window): int
    {
        $current = $this->getCurrentCount($identifier, $window);
        
        switch ($window) {
            case 'hour':
                return max(0, $this->config->getRequestsPerHour() - $current);
            case 'minute':
                return max(0, $this->config->getRequestsPerMinute() - $current);
            case 'burst':
                return max(0, $this->config->getBurstLimit() - $current);
            default:
                return 0;
        }
    }

    /**
     * Increment request counters
     */
    private function incrementCounters(string $identifier): void
    {
        $cache = $this->cacheFrontendPool->get('default');
        
        // Increment hourly counter
        $this->incrementCounter($identifier, 'hour', 3600); // 1 hour
        
        // Increment minute counter
        $this->incrementCounter($identifier, 'minute', 60); // 1 minute
        
        // Increment burst counter (reset every 10 seconds)
        $this->incrementCounter($identifier, 'burst', 10); // 10 seconds
    }

    /**
     * Increment individual counter
     */
    private function incrementCounter(string $identifier, string $window, int $ttl): void
    {
        $cache = $this->cacheFrontendPool->get('default');
        $cacheKey = $this->getCacheKey($identifier, $window);
        
        $current = $this->getCurrentCount($identifier, $window);
        $cache->save($current + 1, $cacheKey, [], $ttl);
    }

    /**
     * Get cache key for time window
     */
    private function getCacheKey(string $identifier, string $window): string
    {
        $timestamp = $this->getWindowTimestamp($window);
        
        switch ($window) {
            case 'hour':
                return sprintf(self::CACHE_KEY_HOUR, $identifier . '_' . $timestamp);
            case 'minute':
                return sprintf(self::CACHE_KEY_MINUTE, $identifier . '_' . $timestamp);
            case 'burst':
                return sprintf(self::CACHE_KEY_BURST, $identifier . '_' . $timestamp);
            default:
                return $identifier . '_' . $window . '_' . $timestamp;
        }
    }

    /**
     * Get timestamp for time window
     */
    private function getWindowTimestamp(string $window): int
    {
        $now = time();
        
        switch ($window) {
            case 'hour':
                return (int) ($now / 3600); // Hour buckets
            case 'minute':
                return (int) ($now / 60); // Minute buckets
            case 'burst':
                return (int) ($now / 10); // 10-second buckets
            default:
                return $now;
        }
    }

    /**
     * Get reset time for time window
     */
    private function getResetTime(string $window): int
    {
        $now = time();
        
        switch ($window) {
            case 'hour':
                return 3600 - ($now % 3600);
            case 'minute':
                return 60 - ($now % 60);
            case 'burst':
                return 10 - ($now % 10);
            default:
                return 60;
        }
    }

    /**
     * Handle rate limit violation
     */
    private function handleViolation(string $identifier, string $violationType): void
    {
        $this->logViolation($identifier, $violationType);
        $this->blockIdentifier($identifier);
    }

    /**
     * Block identifier for configured duration
     */
    private function blockIdentifier(string $identifier): void
    {
        $cache = $this->cacheFrontendPool->get('default');
        $cacheKey = sprintf(self::CACHE_KEY_BLOCKED, $identifier);
        $blockDuration = $this->config->getBlockDuration() * 60; // Convert minutes to seconds
        
        $blockData = [
            'blocked_at' => time(),
            'expires_at' => time() + $blockDuration,
            'identifier' => $identifier
        ];
        
        $cache->save(json_encode($blockData), $cacheKey, [], $blockDuration);
        
        $this->logDebug("Blocked identifier {$identifier} for {$blockDuration} seconds");
    }

    /**
     * Log rate limit violation
     */
    private function logViolation(string $identifier, string $violationType): void
    {
        if ($this->config->isDebugMode()) {
            $this->logger->warning('Rate limit violation', [
                'identifier' => $identifier,
                'violation_type' => $violationType,
                'timestamp' => time(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Log debug message if debug mode is enabled
     */
    private function logDebug(string $message, array $context = []): void
    {
        if ($this->config->isDebugMode()) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * Clear rate limiting data for identifier
     */
    public function clearRateLimit(string $identifier): void
    {
        $cache = $this->cacheFrontendPool->get('default');
        
        // Clear all time windows
        foreach (['hour', 'minute', 'burst'] as $window) {
            $cacheKey = $this->getCacheKey($identifier, $window);
            $cache->remove($cacheKey);
        }
        
        // Clear block status
        $blockKey = sprintf(self::CACHE_KEY_BLOCKED, $identifier);
        $cache->remove($blockKey);
        
        $this->logDebug("Cleared rate limiting data for identifier {$identifier}");
    }

    /**
     * Get rate limiting status for identifier
     */
    public function getStatus(string $identifier): array
    {
        return [
            'identifier' => $identifier,
            'is_blocked' => $this->isBlocked($identifier),
            'block_time_remaining' => $this->getBlockTimeRemaining($identifier),
            'counts' => [
                'hourly' => $this->getCurrentCount($identifier, 'hour'),
                'minute' => $this->getCurrentCount($identifier, 'minute'),
                'burst' => $this->getCurrentCount($identifier, 'burst')
            ],
            'limits' => [
                'hourly' => $this->config->getRequestsPerHour(),
                'minute' => $this->config->getRequestsPerMinute(),
                'burst' => $this->config->getBurstLimit()
            ],
            'remaining' => [
                'hourly' => $this->getRemainingRequests($identifier, 'hour'),
                'minute' => $this->getRemainingRequests($identifier, 'minute'),
                'burst' => $this->getRemainingRequests($identifier, 'burst')
            ]
        ];
    }
}