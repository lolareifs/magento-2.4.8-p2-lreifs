<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Service\Helper;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Authorization\Model\CompositeUserContext;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Context Helper Service
 * Centralizes common context methods to avoid code duplication
 */
class ContextHelper
{
    public function __construct(
        private readonly RemoteAddress $remoteAddress,
        private readonly CompositeUserContext $userContext,
        private readonly AdminSession $adminSession,
        private readonly CustomerSession $customerSession
    ) {}

    /**
     * Get client IP address
     *
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->remoteAddress->getRemoteAddress() ?: 'unknown';
    }

    /**
     * Get user agent from HTTP headers
     *
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get current admin user ID (unified implementation)
     * Uses API context first (for REST calls), falls back to admin session
     *
     * @return int|null
     */
    public function getCurrentAdminUserId(): ?int
    {
        // First try API context (for REST API calls)
        $userId = $this->userContext->getUserId();
        $userType = $this->userContext->getUserType();
        
        if ($userType === \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN && $userId) {
            return (int)$userId;
        }
        
        // Fallback to admin session (for web interface)
        if ($this->adminSession->isLoggedIn()) {
            $user = $this->adminSession->getUser();
            if ($user && $user->getId()) {
                return (int)$user->getId();
            }
        }
        
        return null;
    }

    /**
     * Get current customer ID
     *
     * @return int|null
     */
    public function getCurrentCustomerId(): ?int
    {
        // First try API context (for REST API calls)
        $userId = $this->userContext->getUserId();
        $userType = $this->userContext->getUserType();
        
        if ($userType === \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER && $userId) {
            return (int)$userId;
        }
        
        // Fallback to customer session (for web interface)
        if ($this->customerSession->isLoggedIn()) {
            return (int)$this->customerSession->getCustomerId();
        }
        
        return null;
    }

    /**
     * Get context information for audit logging
     *
     * @return array
     */
    public function getAuditContext(): array
    {
        return [
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'admin_user_id' => $this->getCurrentAdminUserId(),
            'customer_id' => $this->getCurrentCustomerId(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}