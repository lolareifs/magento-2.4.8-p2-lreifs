<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for quote extension search results
 * 
 * @api
 */
interface QuoteExtensionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get quote extensions list
     *
     * @return \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface[]
     */
    public function getItems();

    /**
     * Set quote extensions list
     *
     * @param \Lreifs\Multiquotes\Api\Data\QuoteExtensionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}