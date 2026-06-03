<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Infrastructure\Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CMS aggregate root: a dynamic, SEO-aware page composed of ordered builder
 * blocks ({@see PageSection}).
 *
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property PageType $type
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $content
 * @property PageStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Page extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'cms_pages';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'meta_title',
        'meta_description',
        'content',
        'status',
        'published_at',
    ];

    protected $casts = [
        'type' => PageType::class,
        'status' => PageStatus::class,
        'published_at' => 'datetime',
    ];

    /**
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('position');
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

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
