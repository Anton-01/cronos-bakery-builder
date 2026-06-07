<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Infrastructure\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product Builder aggregate root: a configurable product (e.g. Muse Blanc,
 * Studio Cake) composed of options, their values and conditional rules.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $base_price_amount
 * @property string $currency
 * @property bool $is_active
 */
class Product extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'pb_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'base_price_amount',
        'currency',
        'discount_type',
        'discount_value',
        'tax_class',
        'vat',
        'tags',
        'is_active',
        'position',
    ];

    protected $casts = [
        'base_price_amount' => 'integer',
        'discount_value' => 'integer',
        'vat' => 'integer',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @return HasMany<Option, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return HasMany<OptionRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(OptionRule::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductOptionLink, $this>
     */
    public function optionLinks(): HasMany
    {
        return $this->hasMany(ProductOptionLink::class)->orderBy('position');
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
