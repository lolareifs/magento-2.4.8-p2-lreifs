<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Service\Guard;

use Lreifs\Multiquotes\Api\QuoteExtensionRepositoryInterface;
use Lreifs\Multiquotes\Exception\ImmutableQuoteModificationException;
use Lreifs\Multiquotes\Service\Audit\AuditLogger;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

/**
 * Immutable Quote Guard Service
 * Centralized protection logic for immutable quotes
 */
class ImmutableQuoteGuard
{
    private const PROTECTED_ACTIONS = [
        'add_product',
        'update_quantity',
        'remove_item',
        'update_address',
        'apply_coupon',
        'remove_coupon',
        'change_shipping_method',
        'update_payment_method'
    ];

    public function __construct(
        private readonly QuoteExtensionRepositoryInterface $repository,
        private readonly AuditLogger $auditLogger,
        private readonly EventManagerInterface $eventManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly AdminSession $adminSession,
        private readonly CustomerSession $customerSession,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Prevent modification of immutable quotes
     *
     * @param int $quoteId
     * @param string $action
     * @param array $context
     * @return void
     * @throws ImmutableQuoteModificationException
     */
    public function preventModification(int $quoteId, string $action, array $context = []): void
    {
        if (!$this->isProtectedAction($action)) {
            return; // Allow non-protected actions
        }

        try {
            $extension = $this->repository->getByQuoteId($quoteId);
        } catch (NoSuchEntityException $e) {
            // If no extension exists, quote is mutable
            return;
        }

        if (!$extension->isImmutable()) {
            return; // Allow modifications to mutable quotes
        }

        // Build prevention context
        $preventionContext = array_merge($context, [
            'quote_id' => $quoteId,
            'action' => $action,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id(),
            'customer_id' => $this->getCurrentCustomerId(),
            'admin_user_id' => $this->getCurrentAdminUserId(),
            'timestamp' => date('c'),
            'prevention_reason' => 'quote_is_immutable'
        ]);

        // Log the prevention attempt for security monitoring
        $this->auditLogger->logPreventionAttempt($quoteId, $action, $preventionContext);

        // Dispatch prevention event for extensibility
        $this->eventManager->dispatch('lreifs_quote_modification_blocked', [
            'quote_id' => $quoteId,
            'action' => $action,
            'context' => $preventionContext,
            'extension' => $extension
        ]);

        // Log warning for security monitoring
        $this->logger->warning('Immutable quote modification blocked', [
            'quote_id' => $quoteId,
            'blocked_action' => $action,
            'customer_id' => $preventionContext['customer_id'],
            'ip_address' => $preventionContext['ip_address'],
            'severity' => 'medium'
        ]);

        // Throw exception with context
        throw new ImmutableQuoteModificationException(
            __('This quote is locked and cannot be modified. Action: %1', $action),
            null,
            0,
            $preventionContext
        );
    }

    /**
     * Check if quote is immutable
     *
     * @param int $quoteId
     * @return bool
     */
    public function isQuoteImmutable(int $quoteId): bool
    {
        try {
            $extension = $this->repository->getByQuoteId($quoteId);
            return $extension->isImmutable();
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Check if action is protected
     *
     * @param string $action
     * @return bool
     */
    private function isProtectedAction(string $action): bool
    {
        return in_array($action, self::PROTECTED_ACTIONS, true);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp(): string
    {
        return $this->remoteAddress->getRemoteAddress() ?: 'unknown';
    }

    /**
     * Get current customer ID
     *
     * @return int|null
     */
    private function getCurrentCustomerId(): ?int
    {
        if ($this->customerSession->isLoggedIn()) {
            return (int)$this->customerSession->getCustomerId();
        }
        return null;
    }

    /**
     * Get current admin user ID
     *
     * @return int|null
     */
    private function getCurrentAdminUserId(): ?int
    {
        if ($this->adminSession->isLoggedIn()) {
            return (int)$this->adminSession->getUser()->getId();
        }
        return null;
    }
}