<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Repositories;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Shared\Domain\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Persistence contract for the Product Builder product aggregate.
 */
interface ProductRepositoryInterface extends RepositoryInterface
{
    /**
     * Active product by slug with its full configuration graph eager-loaded.
     */
    public function findActiveConfiguration(string $slug): ?Product;

    /**
     * Any product by id with its full configuration graph eager-loaded.
     */
    public function findConfiguration(string $id): ?Product;

    /**
     * @return Collection<int, Product>
     */
    public function activeProducts(): Collection;
}
