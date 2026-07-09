<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A global, reusable option template independent of any specific product.
 * Templates can be linked to products via ProductOptionLink.
 *
 * @property int $id
 * @property string $key
 * @property string $label
 * @property OptionType $type
 * @property string|null $help_text
 * @property bool $is_required
 * @property int $position
 * @property array<string, mixed>|null $config
 */
class OptionTemplate extends Model
{
    protected $table = 'pb_option_templates';

    protected $fillable = [
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
     * @return HasMany<OptionTemplateValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(OptionTemplateValue::class, 'template_id')->orderBy('position');
    }

    /**
     * @return HasMany<ProductOptionLink, $this>
     */
    public function productLinks(): HasMany
    {
        return $this->hasMany(ProductOptionLink::class, 'template_id');
    }
}
