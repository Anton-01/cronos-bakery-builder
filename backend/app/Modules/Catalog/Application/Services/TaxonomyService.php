<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Services;

use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\AttributeValue;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Collection as ProductCollection;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Support\Collection;

/**
 * Administrative CRUD for catalog taxonomy (categories, collections,
 * attributes & values) plus syncing a product's classifications.
 */
final class TaxonomyService
{
    // --- Categories ---------------------------------------------------------

    public function categories(): Collection
    {
        return Category::query()->whereNull('parent_id')->with('children')->orderBy('position')->get();
    }

    /** @param array<string, mixed> $attributes */
    public function createCategory(array $attributes): Category
    {
        return Category::create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateCategory(string $id, array $attributes): Category
    {
        $category = Category::query()->findOrFail($id);
        $category->update($attributes);

        return $category->refresh();
    }

    public function deleteCategory(string $id): void
    {
        Category::query()->findOrFail($id)->delete();
    }

    // --- Collections --------------------------------------------------------

    public function collections(): Collection
    {
        return ProductCollection::query()->orderBy('position')->get();
    }

    /** @param array<string, mixed> $attributes */
    public function createCollection(array $attributes): ProductCollection
    {
        return ProductCollection::create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateCollection(string $id, array $attributes): ProductCollection
    {
        $collection = ProductCollection::query()->findOrFail($id);
        $collection->update($attributes);

        return $collection->refresh();
    }

    public function deleteCollection(string $id): void
    {
        ProductCollection::query()->findOrFail($id)->delete();
    }

    // --- Attributes & values ------------------------------------------------

    public function attributes(): Collection
    {
        return Attribute::query()->with('values')->orderBy('position')->get();
    }

    /** @param array<string, mixed> $attributes */
    public function createAttribute(array $attributes): Attribute
    {
        return Attribute::create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateAttribute(string $id, array $attributes): Attribute
    {
        $attribute = Attribute::query()->findOrFail($id);
        $attribute->update($attributes);

        return $attribute->refresh()->load('values');
    }

    public function deleteAttribute(string $id): void
    {
        Attribute::query()->findOrFail($id)->delete();
    }

    /** @param array<string, mixed> $attributes */
    public function addAttributeValue(string $attributeId, array $attributes): AttributeValue
    {
        $attribute = Attribute::query()->findOrFail($attributeId);

        return $attribute->values()->create($attributes);
    }

    public function deleteAttributeValue(string $attributeId, string $valueId): void
    {
        AttributeValue::query()->where('attribute_id', $attributeId)->whereKey($valueId)->delete();
    }

    // --- Product taxonomy sync ---------------------------------------------

    /**
     * @param  array{categories?: array<int,string>, primary_category?: string|null,
     *     collections?: array<int,string>, attribute_values?: array<int,string>, tags?: array<int,string>}  $payload
     */
    public function syncProductTaxonomy(string $productId, array $payload): Product
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail($productId);

        if (array_key_exists('categories', $payload)) {
            $primary = $payload['primary_category'] ?? null;
            $sync = [];
            foreach ($payload['categories'] as $categoryId) {
                $sync[$categoryId] = ['is_primary' => $categoryId === $primary];
            }
            $product->categories()->sync($sync);
        }

        if (array_key_exists('collections', $payload)) {
            $product->collections()->sync($payload['collections']);
        }

        if (array_key_exists('attribute_values', $payload)) {
            $product->attributeValues()->sync($payload['attribute_values']);
        }

        if (array_key_exists('tags', $payload)) {
            $product->tags()->sync($payload['tags']);
        }

        return $product->load(['categories', 'collections', 'attributeValues.attribute', 'tags']);
    }
}
