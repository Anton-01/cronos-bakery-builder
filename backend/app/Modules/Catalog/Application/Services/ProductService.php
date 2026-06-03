<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Services;

use App\Modules\Catalog\Application\DTO\ProductData;
use App\Modules\Catalog\Domain\Events\ProductCreated;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Application service orchestrating catalog use-cases. Controllers depend on
 * this service; it coordinates repositories and emits domain events, keeping
 * business orchestration out of the HTTP layer.
 */
final readonly class ProductService
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function list(): Collection
    {
        return $this->products->activeProducts();
    }

    public function get(string $id): Product
    {
        return $this->products->findOrFail($id);
    }

    public function create(ProductData $data): Product
    {
        /** @var Product $product */
        $product = $this->products->create($data->toArray());

        ProductCreated::dispatch($product);

        return $product;
    }

    public function update(string $id, ProductData $data): Product
    {
        /** @var Product $product */
        $product = $this->products->update($id, $data->toArray());

        return $product;
    }

    public function delete(string $id): void
    {
        $this->products->delete($id);
    }
}
