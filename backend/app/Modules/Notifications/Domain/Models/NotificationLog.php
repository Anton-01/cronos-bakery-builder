<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A record of a dispatched notification — for traceability and idempotency.
 *
 * @property string $id
 * @property string $event
 * @property string $recipient
 * @property string $subject
 * @property string $status
 * @property string|null $dedupe_key
 * @property array<string, mixed>|null $context
 */
class NotificationLog extends Model
{
    use HasUuids;

    protected $table = 'notification_logs';

    protected $fillable = [
        'event',
        'recipient',
        'subject',
        'body',
        'status',
        'dedupe_key',
        'context',
        'sent_at',
    ];

    protected $casts = [
        'context' => 'array',
        'sent_at' => 'datetime',
    ];
}
