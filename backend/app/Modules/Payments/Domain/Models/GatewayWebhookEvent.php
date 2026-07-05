<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Idempotency ledger for inbound webhooks. The (payment_gateway_id,
 * provider_event_id) unique constraint guarantees a provider event is only
 * ever processed once, even under concurrent duplicate deliveries.
 *
 * @property int $id
 * @property int $payment_gateway_id
 * @property string $provider_event_id
 * @property string|null $event_type
 * @property array<string, mixed>|null $payload
 */
class GatewayWebhookEvent extends Model
{
    protected $table = 'gateway_webhook_events';

    protected $fillable = [
        'payment_gateway_id',
        'provider_event_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payment_gateway_id' => 'integer',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
