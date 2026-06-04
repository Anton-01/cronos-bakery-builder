<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a global option template to a specific product, optionally restricting
 * which template values are enabled and providing per-product legend text.
 *
 * @property string $id
 * @property string $product_id
 * @property string $template_id
 * @property string|null $legend
 * @property array<int, string>|null $enabled_value_ids
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
        'enabled_value_ids',
        'position',
    ];

    protected $casts = [
        'enabled_value_ids' => 'array',
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
}
