<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable snapshot of a configured cake at the moment the order was placed.
 *
 * @property string $id
 * @property string $order_id
 * @property string $product_name
 * @property array<string, mixed> $configuration
 * @property int $unit_price_amount
 * @property int $quantity
 * @property int $line_total_amount
 */
class OrderItem extends Model
{
    use HasUuids;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_slug',
        'configuration',
        'unit_price_amount',
        'quantity',
        'line_total_amount',
    ];

    protected $casts = [
        'configuration' => 'array',
        'unit_price_amount' => 'integer',
        'quantity' => 'integer',
        'line_total_amount' => 'integer',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
