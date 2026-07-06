<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Infrastructure\Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A payment attempt against an order through a configured gateway instance.
 * `raw_response` keeps the last provider payload for audit purposes and
 * `idempotency_key` is unique per gateway, so retried initiations can never
 * duplicate a charge.
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $order_id UUID — Orders module pending identity migration (§13)
 * @property int $payment_gateway_id
 * @property string|null $provider_transaction_id
 * @property PaymentStatus $status
 * @property int $amount
 * @property string $currency
 * @property array<string, mixed>|null $raw_response
 * @property array<string, mixed>|null $checkout
 * @property string $idempotency_key
 * @property int $attempts
 */
class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'brand_id',
        'order_id',
        'user_id',
        'payment_gateway_id',
        'provider_transaction_id',
        'amount',
        'currency',
        'status',
        'raw_response',
        'checkout',
        'idempotency_key',
        'attempts',
        'paid_at',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'payment_gateway_id' => 'integer',
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'attempts' => 'integer',
        'raw_response' => 'array',
        'checkout' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id')->withTrashed();
    }

    /**
     * @return HasMany<TransactionEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TransactionEvent::class)->latest();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForBrand(Builder $query, ?int $brandId): void
    {
        if ($brandId !== null) {
            $query->where('brand_id', $brandId);
        }
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
