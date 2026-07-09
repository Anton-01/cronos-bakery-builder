<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Application\Services;

use App\Modules\ProductBuilder\Domain\Models\Option;
use App\Modules\ProductBuilder\Domain\Models\OptionRule;
use App\Modules\ProductBuilder\Domain\Models\OptionValue;
use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Administrative CRUD for the Product Builder: products, options, option values
 * and conditional rules.
 */
final class ProductAdminService
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function all(): Collection
    {
        return Product::query()
            ->withCount('options')
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    public function get(int $id): Product
    {
        return $this->products->findConfiguration($id)
            ?? throw new NotFoundHttpException('Product not found.');
    }

    /** @param array<string, mixed> $attributes */
    public function createProduct(array $attributes): Product
    {
        return $this->products->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateProduct(int $id, array $attributes): Product
    {
        return $this->products->update($id, $attributes);
    }

    public function deleteProduct(int $id): void
    {
        $this->products->delete($id);
    }

    // --- Options ------------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function addOption(int $productId, array $attributes): Option
    {
        $product = $this->get($productId);
        $attributes['position'] = $attributes['position'] ?? (int) $product->options()->max('position') + 1;

        return $product->options()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateOption(int $productId, int $optionId, array $attributes): Option
    {
        $option = $this->findOption($productId, $optionId);
        $option->update($attributes);

        return $option->refresh();
    }

    public function deleteOption(int $productId, int $optionId): void
    {
        $this->findOption($productId, $optionId)->delete();
    }

    // --- Option values ------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function addValue(int $productId, int $optionId, array $attributes): OptionValue
    {
        $option = $this->findOption($productId, $optionId);
        $attributes['position'] = $attributes['position'] ?? (int) $option->values()->max('position') + 1;

        return $option->values()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateValue(int $productId, int $optionId, int $valueId, array $attributes): OptionValue
    {
        $value = OptionValue::query()
            ->where('option_id', $optionId)
            ->whereKey($valueId)
            ->firstOr(fn () => throw new NotFoundHttpException('Option value not found.'));

        $this->findOption($productId, $optionId); // ensure the option belongs to the product
        $value->update($attributes);

        return $value->refresh();
    }

    public function deleteValue(int $productId, int $optionId, int $valueId): void
    {
        $this->findOption($productId, $optionId);
        OptionValue::query()->where('option_id', $optionId)->whereKey($valueId)->delete();
    }

    // --- Rules --------------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function addRule(int $productId, array $attributes): OptionRule
    {
        $product = $this->get($productId);

        return $product->rules()->create($attributes);
    }

    public function deleteRule(int $productId, int $ruleId): void
    {
        OptionRule::query()->where('product_id', $productId)->whereKey($ruleId)->delete();
    }

    private function findOption(int $productId, int $optionId): Option
    {
        return Option::query()
            ->where('product_id', $productId)
            ->whereKey($optionId)
            ->firstOr(fn () => throw new NotFoundHttpException('Option not found.'));
    }
}
