<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Events;

use App\Modules\Catalog\Domain\Models\Product;
use App\Shared\Domain\Events\DomainEvent;

/**
 * Raised when a new product is added to the catalog. Other modules (e.g.
 * Notifications, search indexing) may subscribe without coupling to Catalog.
 */
final class ProductCreated extends DomainEvent
{
    public function __construct(public readonly Product $product)
    {
        parent::__construct();
    }
}
