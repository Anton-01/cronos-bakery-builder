<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Infrastructure\Database\Factories\PageFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CMS aggregate root: a brand-scoped, SEO-aware dynamic page composed of
 * ordered builder blocks ({@see PageBlock}).
 *
 * @property int $id
 * @property int $brand_id
 * @property string $title
 * @property string $slug
 * @property PageType $type
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $content
 * @property array<string, mixed>|null $settings
 * @property PageStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Page extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'cms_pages';

    protected $fillable = [
        'brand_id',
        'title',
        'slug',
        'type',
        'meta_title',
        'meta_description',
        'content',
        'settings',
        'status',
        'published_at',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'type' => PageType::class,
        'settings' => 'array',
        'status' => PageStatus::class,
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<PageBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published->value);
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeForBrand(Builder $query, int $brandId): void
    {
        $query->where('brand_id', $brandId);
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
