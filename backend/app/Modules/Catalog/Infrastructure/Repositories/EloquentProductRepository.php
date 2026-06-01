<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Repositories;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Repositories\ProductRepositoryInterface;
use App\Shared\Infrastructure\Repositories\AbstractEloquentRepository;
use Illuminate\Support\Collection;

/**
 * Eloquent-backed implementation of the Product repository contract.
 */
class EloquentProductRepository extends AbstractEloquentRepository implements ProductRepositoryInterface
{
    protected function model(): string
    {
        return Product::class;
    }

    public function activeProducts(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
