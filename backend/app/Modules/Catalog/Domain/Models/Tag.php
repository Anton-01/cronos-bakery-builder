<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A lightweight product label used for cross-cutting grouping and filtering.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'catalog_tags';

    protected $fillable = ['name', 'slug'];

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_product_tag');
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
