<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Infrastructure\Database\Factories\BannerFactory;
use App\Shared\Domain\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An administrable promotional banner shown in a given placement, optionally
 * scheduled with a start/end window.
 *
 * @property int $id
 * @property string $title
 * @property string $image_path
 * @property string|null $link_url
 * @property BannerPlacement $placement
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 */
class Banner extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'image_path',
        'link_url',
        'placement',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'placement' => BannerPlacement::class,
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Active banners whose schedule window currently includes "now".
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }
}
