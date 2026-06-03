<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Services;

use App\Modules\Catalog\Application\DTO\CatalogFilter;
use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Collection as ProductCollection;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Storefront catalog browsing: dynamic, configurable filtering with facets and
 * SEO-friendly category/product lookups (with breadcrumbs).
 */
final class ProductBrowseService
{
    private const EAGER = ['categories', 'collections', 'attributeValues.attribute', 'tags'];

    /**
     * @return LengthAwarePaginator<Product>
     */
    public function filter(CatalogFilter $filter): LengthAwarePaginator
    {
        return $this->buildQuery($filter)->with(self::EAGER)->paginate($filter->perPage);
    }

    public function categoryBySlug(string $slug): ?Category
    {
        return Category::query()->active()->with('parent')->where('slug', $slug)->first();
    }

    public function productBySlug(string $slug): ?Product
    {
        return Product::query()->active()->with(self::EAGER)->where('slug', $slug)->first();
    }

    /**
     * Breadcrumb trail for a product: its primary category chain + the product.
     *
     * @return array<int, array{label: string, slug: string, type: string}>
     */
    public function productBreadcrumbs(Product $product): array
    {
        $crumbs = [['label' => 'Catálogo', 'slug' => '', 'type' => 'catalog']];

        $category = $product->primaryCategory();
        if ($category !== null) {
            foreach ($category->breadcrumbTrail() as $node) {
                $crumbs[] = ['label' => $node->name, 'slug' => $node->slug, 'type' => 'category'];
            }
        }

        $crumbs[] = ['label' => $product->name, 'slug' => $product->slug, 'type' => 'product'];

        return $crumbs;
    }

    /**
     * @return array<int, array{label: string, slug: string, type: string}>
     */
    public function categoryBreadcrumbs(Category $category): array
    {
        $crumbs = [['label' => 'Catálogo', 'slug' => '', 'type' => 'catalog']];

        foreach ($category->breadcrumbTrail() as $node) {
            $crumbs[] = ['label' => $node->name, 'slug' => $node->slug, 'type' => 'category'];
        }

        return $crumbs;
    }

    /**
     * Build the available filter facets (categories, collections, filterable
     * attributes and the price range) for the storefront filter UI.
     *
     * @return array<string, mixed>
     */
    public function facets(): array
    {
        $priceBounds = Product::query()->active()->selectRaw('MIN(price_amount) as min, MAX(price_amount) as max')->first();

        return [
            'categories' => Category::query()->active()->whereNull('parent_id')
                ->with('children')->orderBy('position')->get(),
            'collections' => ProductCollection::query()->where('is_active', true)->orderBy('position')->get(),
            'attributes' => Attribute::query()->filterable()->with('values')->orderBy('position')->get(),
            'price' => [
                'min' => (int) ($priceBounds->min ?? 0),
                'max' => (int) ($priceBounds->max ?? 0),
            ],
        ];
    }

    /**
     * @return Builder<Product>
     */
    private function buildQuery(CatalogFilter $filter): Builder
    {
        $query = Product::query()->active();

        if ($filter->category !== null) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('slug', $filter->category));
        }

        if ($filter->collection !== null) {
            $query->whereHas('collections', fn (Builder $q) => $q->where('slug', $filter->collection));
        }

        if ($filter->tag !== null) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $filter->tag));
        }

        if ($filter->priceMin !== null) {
            $query->where('price_amount', '>=', $filter->priceMin);
        }

        if ($filter->priceMax !== null) {
            $query->where('price_amount', '<=', $filter->priceMax);
        }

        if ($filter->search !== null && $filter->search !== '') {
            $query->where('name', 'like', '%' . $filter->search . '%');
        }

        // AND across attributes, OR within each attribute's selected values.
        foreach ($filter->attributes as $code => $values) {
            if ($values === []) {
                continue;
            }
            $query->whereHas('attributeValues', function (Builder $q) use ($code, $values): void {
                $q->whereIn('value', $values)
                    ->whereHas('attribute', fn (Builder $a) => $a->where('code', $code));
            });
        }

        return $this->applySort($query, $filter->sort);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price_amount'),
            'price_desc' => $query->orderByDesc('price_amount'),
            'name' => $query->orderBy('name'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('position')->orderBy('name'),
        };
    }
}
