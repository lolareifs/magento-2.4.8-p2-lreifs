<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Immutable Quote Modification Exception
 * Thrown when attempting to modify an immutable quote
 */
class ImmutableQuoteModificationException extends LocalizedException
{
    private array $context;

    public function __construct(
        \Magento\Framework\Phrase $phrase,
        \Exception $cause = null,
        int $code = 0,
        array $context = []
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->context = $context;
    }

    /**
     * Get exception context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get quote ID from context
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        return $this->context['quote_id'] ?? null;
    }

    /**
     * Get blocked action from context
     *
     * @return string|null
     */
    public function getBlockedAction(): ?string
    {
        return $this->context['action'] ?? null;
    }

    /**
     * Get customer ID from context
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return $this->context['customer_id'] ?? null;
    }

    /**
     * Get IP address from context
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->context['ip_address'] ?? null;
    }
}