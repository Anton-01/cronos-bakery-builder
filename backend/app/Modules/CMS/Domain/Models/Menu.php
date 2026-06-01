<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Infrastructure\Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A dynamic, location-bound navigation menu composed of nested items.
 *
 * @property string $id
 * @property string $name
 * @property MenuLocation $location
 * @property bool $is_active
 */
class Menu extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'menus';

    protected $fillable = [
        'name',
        'location',
        'is_active',
    ];

    protected $casts = [
        'location' => MenuLocation::class,
        'is_active' => 'boolean',
    ];

    /**
     * All items belonging to the menu (flat).
     *
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position');
    }

    /**
     * Only top-level items, with their nested children eager-loaded.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->with('children');
    }

    protected static function newFactory(): MenuFactory
    {
        return MenuFactory::new();
    }
}
