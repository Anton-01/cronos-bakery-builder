<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\CategoryFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A catalog category (e.g. Floral, Moderno, Mini, Signature). Categories may
 * nest via parent_id to form breadcrumb trails and SEO-friendly hierarchies.
 *
 * @property int $id
 * @property string|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property bool $is_active
 */
class Category extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'catalog_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'image',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_category_product')
            ->withPivot('is_primary');
    }

    /**
     * Self → root ancestor chain (ordered root → … → self) for breadcrumbs.
     *
     * @return EloquentCollection<int, Category>
     */
    public function breadcrumbTrail(): EloquentCollection
    {
        $trail = new EloquentCollection();
        $node = $this;

        while ($node !== null) {
            $trail->prepend($node);
            $node = $node->parent;
        }

        return $trail;
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
