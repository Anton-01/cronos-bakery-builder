<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-defined product attribute (e.g. Tamaño, Sabor, Color). When flagged
 * filterable it is surfaced automatically as a catalog filter facet.
 *
 * @property string $id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property bool $is_filterable
 * @property int $position
 */
class Attribute extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'catalog_attributes';

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_filterable',
        'position',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @return HasMany<AttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    /**
     * @param  Builder<Attribute>  $query
     */
    public function scopeFilterable(Builder $query): void
    {
        $query->where('is_filterable', true);
    }

    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }
}
