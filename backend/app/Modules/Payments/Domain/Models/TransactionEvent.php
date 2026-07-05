<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit-trail entry for a transaction (initiation, webhook, retry, status
 * change, refund, reconciliation) — providing full traceability.
 *
 * @property int $id
 * @property int $transaction_id
 * @property string $type
 * @property string|null $status
 * @property bool|null $signature_valid
 * @property array<string, mixed>|null $payload
 */
class TransactionEvent extends Model
{
    protected $table = 'transaction_events';

    protected $fillable = ['transaction_id', 'type', 'status', 'signature_valid', 'payload'];

    protected $casts = [
        'transaction_id' => 'integer',
        'signature_valid' => 'boolean',
        'payload' => 'array',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
