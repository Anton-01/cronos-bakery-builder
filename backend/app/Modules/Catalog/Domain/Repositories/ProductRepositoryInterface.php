<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repositories;

use App\Shared\Domain\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Persistence contract for the Product aggregate. Lives in the Domain layer so
 * application services depend on the abstraction, not on Eloquent.
 */
interface ProductRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, \App\Modules\Catalog\Domain\Models\Product>
     */
    public function activeProducts(): Collection;
}
