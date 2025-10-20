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
        private readonly LoggerInterface $securityLogger,
        private readonly \Lreifs\Multiquotes\Model\QuoteExtensionAuditRepository $auditRepository
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
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => (isset($quoteExtensionId) && is_numeric($quoteExtensionId)) ? $quoteExtensionId : null,
            'action' => 'quote_created',
            'user_id' => $context['admin_user_id'] ?? $context['customer_id'] ?? null,
            'user_type' => isset($context['admin_user_id']) ? 'admin' : 'customer',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => isset($quoteExtensionId) && is_numeric($quoteExtensionId) ? $quoteExtensionId : null,
            'action' => 'quote_activated',
            'user_id' => $context['admin_user_id'] ?? null,
            'user_type' => 'admin',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => isset($quoteExtensionId) && is_numeric($quoteExtensionId) ? $quoteExtensionId : null,
            'action' => 'quote_deactivated',
            'user_id' => $context['admin_user_id'] ?? null,
            'user_type' => 'admin',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => isset($quoteExtensionId) && is_numeric($quoteExtensionId) ? $quoteExtensionId : null,
            'action' => 'quote_to_order_conversion',
            'user_id' => $context['customer_id'] ?? null,
            'user_type' => 'customer',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => isset($quoteExtensionId) && is_numeric(quoteExtensionId) ? $quoteExtensionId : null,
            'action' => 'quote_deleted',
            'user_id' => $context['admin_user_id'] ?? null,
            'user_type' => 'admin',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $this->auditRepository->save([
            'quote_extension_id' => $quoteId,
            'action' => 'modification_blocked',
            'user_id' => $context['admin_user_id'] ?? $context['customer_id'] ?? null,
            'user_type' => isset($context['admin_user_id']) ? 'admin' : 'customer',
            'old_values' => null,
            'new_values' => null,
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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

        $this->auditRepository->save([
            'quote_extension_id' => isset($context['quote_extension_id']) && is_numeric($context['quote_extension_id']) ? $context['quote_extension_id'] : null,
            'action' => 'api_access',
            'user_id' => $context['admin_user_id'] ?? $context['customer_id'] ?? null,
            'user_type' => isset($context['admin_user_id']) ? 'admin' : 'customer',
            'old_values' => null,
            'new_values' => null,
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
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
        $this->auditRepository->save([
            'quote_extension_id' => (isset($context['quote_id']) && is_numeric($context['quote_id'])) ? $context['quote_id'] : null,
            'action' => 'security_violation',
            'user_id' => $context['admin_user_id'] ?? $context['customer_id'] ?? null,
            'user_type' => isset($context['admin_user_id']) ? 'admin' : 'customer',
            'old_values' => null,
            'new_values' => null,
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log quote expiration
     *
     * @param QuoteExtensionInterface $extension
     * @param array $context
     * @return void
     */
    public function logQuoteExpiration(QuoteExtensionInterface $extension, array $context): void
    {
        $this->logger->info('Quote expired and deactivated', [
            'event' => 'quote_expired',
            'quote_id' => $extension->getQuoteId(),
            'extension_id' => $extension->getEntityId(),
            'customer_id' => $extension->getCustomerId(),
            'expired_at' => $context['expired_at'] ?? null,
            'expiration_date' => $context['expiration_date'] ?? null,
            'automatic' => $context['automatic'] ?? true,
            'manual_override' => $context['manual_override'] ?? false,
            'processed_by' => $context['processed_by'] ?? 'system',
            'admin_user_id' => $context['admin_user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'previous_status' => 'active',
            'new_status' => 'expired',
            'timestamp' => date('c'),
            'compliance_tags' => ['audit_trail', 'quote_lifecycle', 'automatic_expiration']
        ]);
        $quoteExtensionId = $extension->getEntityId();
        $this->auditRepository->save([
            'quote_extension_id' => (isset($quoteExtensionId) && is_numeric($quoteExtensionId)) ? $quoteExtensionId : null,
            'action' => 'quote_expired',
            'user_id' => $context['admin_user_id'] ?? $extension->getCustomerId() ?? null,
            'user_type' => isset($context['admin_user_id']) ? 'admin' : 'customer',
            'old_values' => null,
            'new_values' => json_encode($extension->getData()),
            'context' => json_encode($context),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}