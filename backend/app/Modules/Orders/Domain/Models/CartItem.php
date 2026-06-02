<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A configured cake in the cart, carrying a full configuration snapshot and the
 * authoritative unit price computed at the time it was added.
 *
 * @property string $id
 * @property string $cart_id
 * @property string $product_id
 * @property string $product_name
 * @property string $product_slug
 * @property array<string, mixed> $configuration
 * @property int $unit_price_amount
 * @property string $currency
 * @property int $quantity
 */
class CartItem extends Model
{
    use HasUuids;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_name',
        'product_slug',
        'configuration',
        'unit_price_amount',
        'currency',
        'quantity',
    ];

    protected $casts = [
        'configuration' => 'array',
        'unit_price_amount' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function lineTotal(): int
    {
        return $this->unit_price_amount * $this->quantity;
    }
}
