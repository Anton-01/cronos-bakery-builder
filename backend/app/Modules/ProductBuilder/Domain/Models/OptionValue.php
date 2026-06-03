<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Infrastructure\Database\Factories\OptionValueFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A selectable value of a choice-based option, optionally carrying a price
 * modifier (add / subtract / set) and presentation metadata (color, image).
 *
 * @property string $id
 * @property string $option_id
 * @property string $label
 * @property string $value
 * @property PriceModifierType $price_modifier_type
 * @property int $price_modifier_amount
 * @property array<string, mixed>|null $metadata
 * @property bool $is_default
 * @property int $position
 */
class OptionValue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'pb_option_values';

    protected $fillable = [
        'option_id',
        'label',
        'value',
        'price_modifier_type',
        'price_modifier_amount',
        'metadata',
        'is_default',
        'position',
    ];

    protected $casts = [
        'price_modifier_type' => PriceModifierType::class,
        'price_modifier_amount' => 'integer',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Option, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    protected static function newFactory(): OptionValueFactory
    {
        return OptionValueFactory::new();
    }
}
