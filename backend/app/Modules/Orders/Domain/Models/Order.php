<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Models;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\FulfillmentType;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Infrastructure\Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A placed order with its line items, fulfillment details and totals.
 *
 * @property string $id
 * @property string $number
 * @property int $user_id
 * @property OrderStatus $status
 * @property FulfillmentType $fulfillment_type
 * @property array<string, mixed>|null $shipping_address
 * @property string|null $branch_id
 * @property int $subtotal_amount
 * @property int $total_amount
 * @property string $currency
 */
class Order extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'orders';

    protected $fillable = [
        'number',
        'user_id',
        'status',
        'fulfillment_type',
        'shipping_address',
        'branch_id',
        'pickup_date',
        'pickup_time',
        'subtotal_amount',
        'total_amount',
        'currency',
        'notes',
        'placed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'fulfillment_type' => FulfillmentType::class,
        'shipping_address' => 'array',
        'pickup_date' => 'date',
        'subtotal_amount' => 'integer',
        'total_amount' => 'integer',
        'placed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
