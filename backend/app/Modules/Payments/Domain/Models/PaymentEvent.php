<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit-trail entry for a payment (initiation, webhook, retry, status
 * change, reconciliation) — providing full traceability.
 *
 * @property string $id
 * @property string $payment_id
 * @property string $type
 * @property string|null $status
 * @property bool|null $signature_valid
 * @property array<string, mixed>|null $payload
 */
class PaymentEvent extends Model
{
    use HasUuids;

    protected $table = 'payment_events';

    protected $fillable = ['payment_id', 'type', 'status', 'signature_valid', 'payload'];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload' => 'array',
    ];

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
