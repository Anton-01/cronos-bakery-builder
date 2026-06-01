<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single navigation entry. Items may nest one or more levels via parent_id
 * (e.g. Pasteles → Floral, Moderno, Mini Cakes).
 *
 * @property string $id
 * @property string $menu_id
 * @property string|null $parent_id
 * @property string $label
 * @property string|null $url
 * @property string $target
 * @property int $position
 * @property bool $is_active
 */
class MenuItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'target',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->with('children');
    }
}
