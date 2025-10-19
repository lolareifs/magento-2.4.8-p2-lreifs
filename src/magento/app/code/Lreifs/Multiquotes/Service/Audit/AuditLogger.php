<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Service\Audit;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface;
use Psr\Log\LoggerInterface;

/**
 * Audit Logger Service
 * Provides comprehensive audit logging for immutable quotes
 */
class AuditLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LoggerInterface $securityLogger
    ) {}

    /**
     * Log quote creation
     *
     * @param QuoteExtensionInterface $extension
     * @param array $context
     * @return void
     */
    public function logQuoteCreation(QuoteExtensionInterface $extension, array $context): void
    {
        $this->logger->info('Immutable quote created', [
            'event' => 'quote_created',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'customer_id' => $context['customer_id'] ?? null,
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'customer_reference' => $extension->getCustomerReference(),
            'custom_fee' => $extension->getCustomFee(),
            'metadata' => $extension->getMetadata(),
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'quote_lifecycle']
        ]);
    }

    /**
     * Log quote activation
     *
     * @param QuoteExtensionInterface $extension
     * @param array $context
     * @return void
     */
    public function logQuoteActivation(QuoteExtensionInterface $extension, array $context): void
    {
        $this->logger->info('Immutable quote activated', [
            'event' => 'quote_activated',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'activated_at' => $context['activated_at'],
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'quote_activation']
        ]);
    }

    /**
     * Log quote deactivation
     *
     * @param QuoteExtensionInterface $extension
     * @param array $context
     * @return void
     */
    public function logQuoteDeactivation(QuoteExtensionInterface $extension, array $context): void
    {
        $this->logger->info('Immutable quote deactivated', [
            'event' => 'quote_deactivated',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'deactivated_at' => $context['deactivated_at'],
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'quote_deactivation']
        ]);
    }

    /**
     * Log order conversion
     *
     * @param QuoteExtensionInterface $extension
     * @param int $orderId
     * @param array $context
     * @return void
     */
    public function logOrderConversion(QuoteExtensionInterface $extension, int $orderId, array $context): void
    {
        $this->logger->info('Immutable quote converted to order', [
            'event' => 'quote_to_order_conversion',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'order_id' => $orderId,
            'customer_id' => $context['customer_id'],
            'converted_at' => $context['converted_at'],
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'order_conversion', 'business_transaction']
        ]);
    }

    /**
     * Log quote deletion
     *
     * @param QuoteExtensionInterface $extension
     * @param array $context
     * @return void
     */
    public function logQuoteDeletion(QuoteExtensionInterface $extension, array $context): void
    {
        $this->logger->warning('Immutable quote deleted', [
            'event' => 'quote_deleted',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'deleted_at' => $context['deleted_at'],
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'quote_deletion', 'data_removal']
        ]);
    }

    /**
     * Log prevention attempt (security logging)
     *
     * @param int $quoteId
     * @param string $action
     * @param array $context
     * @return void
     */
    public function logPreventionAttempt(int $quoteId, string $action, array $context): void
    {
        $this->securityLogger->warning('Immutable quote modification blocked', [
            'event' => 'modification_blocked',
            'event_type' => 'security_prevention',
            'quote_id' => $quoteId,
            'blocked_action' => $action,
            'prevention_reason' => $context['prevention_reason'] ?? 'quote_is_immutable',
            'customer_id' => $context['customer_id'] ?? null,
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'session_id' => $context['session_id'] ?? null,
            'timestamp' => $context['timestamp'] ?? date('c'),
            'severity' => 'medium',
            'compliance_tags' => ['security_incident', 'access_control', 'prevention']
        ]);
    }

    /**
     * Log API access
     *
     * @param string $endpoint
     * @param string $method
     * @param array $context
     * @return void
     */
    public function logApiAccess(string $endpoint, string $method, array $context): void
    {
        $this->logger->info('Quote extension API accessed', [
            'event' => 'api_access',
            'endpoint' => $endpoint,
            'method' => $method,
            'customer_id' => $context['customer_id'] ?? null,
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'request_id' => $context['request_id'] ?? null,
            'response_status' => $context['response_status'] ?? null,
            'execution_time' => $context['execution_time'] ?? null,
            'timestamp' => date('c'),
            'compliance_tags' => ['api_access', 'audit_trail']
        ]);
    }

    /**
     * Log security violation
     *
     * @param string $violation
     * @param array $context
     * @return void
     */
    public function logSecurityViolation(string $violation, array $context): void
    {
        $this->securityLogger->critical('Security violation detected', [
            'event' => 'security_violation',
            'event_type' => 'security_incident',
            'violation_type' => $violation,
            'severity' => 'high',
            'quote_id' => $context['quote_id'] ?? null,
            'customer_id' => $context['customer_id'] ?? null,
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'attempted_action' => $context['action'] ?? null,
            'blocked_reason' => $context['reason'] ?? null,
            'timestamp' => date('c'),
            'requires_investigation' => true,
            'compliance_tags' => ['security_incident', 'potential_breach', 'investigation_required']
        ]);
    }
}