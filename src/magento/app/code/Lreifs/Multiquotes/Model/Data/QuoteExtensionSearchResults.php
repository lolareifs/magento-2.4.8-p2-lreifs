<?php
/**
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lreifs\Multiquotes\Model\Data;

use Lreifs\Multiquotes\Api\Data\QuoteExtensionSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Service Data Object with Quote Extension search results
 */
class QuoteExtensionSearchResults extends SearchResults implements QuoteExtensionSearchResultsInterface
{
}