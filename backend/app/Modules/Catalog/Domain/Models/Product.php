<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalog aggregate root: a sellable artisanal cake product.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price_amount
 * @property string $currency
 * @property bool $is_active
 */
class Product extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'catalog_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_amount',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'price_amount' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
