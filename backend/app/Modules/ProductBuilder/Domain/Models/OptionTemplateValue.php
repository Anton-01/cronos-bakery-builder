<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A selectable value belonging to a global option template, optionally
 * carrying a price modifier (add / subtract / set) and presentation metadata.
 *
 * @property string $id
 * @property string $template_id
 * @property string $label
 * @property string $value
 * @property PriceModifierType $price_modifier_type
 * @property int $price_modifier_amount
 * @property array<string, mixed>|null $metadata
 * @property bool $is_default
 * @property int $position
 */
class OptionTemplateValue extends Model
{
    use HasUuids;

    protected $table = 'pb_option_template_values';

    protected $fillable = [
        'template_id',
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
     * @return BelongsTo<OptionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(OptionTemplate::class, 'template_id');
    }
}
