<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Infrastructure\Database\Factories\AttributeValueFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A possible value of an attribute (e.g. "Grande" of Tamaño, "#f7c5d9" of Color).
 *
 * @property string $id
 * @property string $attribute_id
 * @property string $label
 * @property string $value
 * @property array<string, mixed>|null $metadata
 */
class AttributeValue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'catalog_attribute_values';

    protected $fillable = [
        'attribute_id',
        'label',
        'value',
        'metadata',
        'position',
    ];

    protected $casts = [
        'metadata' => 'array',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_attribute_value_product');
    }

    protected static function newFactory(): AttributeValueFactory
    {
        return AttributeValueFactory::new();
    }
}
