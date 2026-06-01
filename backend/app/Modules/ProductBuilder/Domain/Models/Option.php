<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Infrastructure\Database\Factories\OptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single configurable option of a product (e.g. Forma, Color, Decoraciones).
 *
 * @property string $id
 * @property string $product_id
 * @property string $key
 * @property string $label
 * @property OptionType $type
 * @property string|null $help_text
 * @property bool $is_required
 * @property int $position
 * @property array<string, mixed>|null $config
 */
class Option extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'pb_options';

    protected $fillable = [
        'product_id',
        'key',
        'label',
        'type',
        'help_text',
        'is_required',
        'position',
        'config',
    ];

    protected $casts = [
        'type' => OptionType::class,
        'is_required' => 'boolean',
        'position' => 'integer',
        'config' => 'array',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('position');
    }

    protected static function newFactory(): OptionFactory
    {
        return OptionFactory::new();
    }
}
