<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentMode;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Infrastructure\Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A payment attempt against an order through a specific gateway and mode.
 *
 * @property string $id
 * @property string $order_id
 * @property GatewayType $gateway
 * @property PaymentMode $mode
 * @property PaymentStatus $status
 * @property int $amount
 * @property string $currency
 * @property string|null $reference
 * @property string $idempotency_key
 * @property int $attempts
 * @property array<string, mixed>|null $metadata
 */
class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'user_id',
        'gateway',
        'mode',
        'status',
        'amount',
        'currency',
        'reference',
        'idempotency_key',
        'attempts',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'gateway' => GatewayType::class,
        'mode' => PaymentMode::class,
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'attempts' => 'integer',
        'metadata' => 'array',
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
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest();
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
