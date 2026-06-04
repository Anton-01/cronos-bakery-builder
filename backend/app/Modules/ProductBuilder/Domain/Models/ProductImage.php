<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An image belonging to a Product Builder product gallery.
 *
 * @property string $id
 * @property string $product_id
 * @property string $path
 * @property string|null $name
 * @property string|null $alt_text
 * @property int $position
 */
class ProductImage extends Model
{
    use HasUuids;

    protected $table = 'pb_product_images';

    protected $fillable = [
        'product_id',
        'path',
        'name',
        'alt_text',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
