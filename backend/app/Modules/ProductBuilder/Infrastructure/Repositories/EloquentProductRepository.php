<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Repositories;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Repositories\ProductRepositoryInterface;
use App\Shared\Infrastructure\Repositories\AbstractEloquentRepository;
use Illuminate\Support\Collection;

/**
 * Eloquent-backed implementation of the Product Builder product repository.
 */
class EloquentProductRepository extends AbstractEloquentRepository implements ProductRepositoryInterface
{
    protected function model(): string
    {
        return Product::class;
    }

    public function findActiveConfiguration(string $slug): ?Product
    {
        return $this->configurationQuery()
            ->active()
            ->where('slug', $slug)
            ->first();
    }

    public function findConfiguration(string $id): ?Product
    {
        return $this->configurationQuery()->whereKey($id)->first();
    }

    public function activeProducts(): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function configurationQuery()
    {
        return $this->model->newQuery()->with([
            'options' => fn ($q) => $q->orderBy('position'),
            'options.values' => fn ($q) => $q->orderBy('position'),
            'rules' => fn ($q) => $q->orderBy('position'),
        ]);
    }
}
