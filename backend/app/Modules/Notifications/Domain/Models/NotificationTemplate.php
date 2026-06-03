<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Domain\Models;

use App\Modules\Notifications\Infrastructure\Database\Factories\NotificationTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-configurable email template for an automation event.
 *
 * @property string $id
 * @property string $event
 * @property string $channel
 * @property string $subject
 * @property string $body
 * @property bool $is_active
 */
class NotificationTemplate extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'notification_templates';

    protected $fillable = ['event', 'channel', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function newFactory(): NotificationTemplateFactory
    {
        return NotificationTemplateFactory::new();
    }
}
