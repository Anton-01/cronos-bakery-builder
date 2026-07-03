<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\ProductFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalog aggregate root: a sellable artisanal cake product, classified by
 * categories, collections, attributes and tags for dynamic filtering.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $image
 * @property int $price_amount
 * @property string $currency
 * @property bool $is_active
 * @property int $position
 */
class Product extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'catalog_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'image',
        'price_amount',
        'currency',
        'is_active',
        'position',
    ];

    protected $casts = [
        'price_amount' => 'integer',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'catalog_category_product')
            ->withPivot('is_primary');
    }

    /**
     * @return BelongsToMany<Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'catalog_collection_product');
    }

    /**
     * @return BelongsToMany<AttributeValue, $this>
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'catalog_attribute_value_product');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'catalog_product_tag');
    }

    /**
     * The product's primary category (falls back to the first attached).
     */
    public function primaryCategory(): ?Category
    {
        return $this->categories->firstWhere('pivot.is_primary', true)
            ?? $this->categories->first();
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
