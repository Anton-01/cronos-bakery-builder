<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a global option template to a specific product, optionally excluding
 * some of its values and providing per-product legend text.
 *
 * Exclusion semantics: `excluded_value_ids` lists the template value IDs this
 * product hides. null / [] means the product inherits every template value,
 * including values added to the template after the link was created.
 *
 * @property string $id
 * @property string $product_id
 * @property string $template_id
 * @property string|null $legend
 * @property array<int, string>|null $excluded_value_ids
 * @property int $position
 */
class ProductOptionLink extends Model
{
    use HasUuids;

    protected $table = 'pb_product_option_links';

    protected $fillable = [
        'product_id',
        'template_id',
        'legend',
        'excluded_value_ids',
        'position',
    ];

    protected $casts = [
        'excluded_value_ids' => 'array',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<OptionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(OptionTemplate::class, 'template_id');
    }

    public function isValueExcluded(string $valueId): bool
    {
        return in_array($valueId, $this->excluded_value_ids ?? [], true);
    }

    /**
     * Template values visible for this product (exclusions applied).
     *
     * @return Collection<int, OptionTemplateValue>
     */
    public function effectiveValues(): Collection
    {
        $values = $this->template?->values ?? new Collection();

        return $values
            ->reject(fn (OptionTemplateValue $value): bool => $this->isValueExcluded((string) $value->id))
            ->values();
    }
}
