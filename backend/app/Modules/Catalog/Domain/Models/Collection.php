<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\CollectionFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A curated collection of products (e.g. seasonal lineups).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class Collection extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'catalog_collections';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_collection_product');
    }

    protected static function newFactory(): CollectionFactory
    {
        return CollectionFactory::new();
    }
}
