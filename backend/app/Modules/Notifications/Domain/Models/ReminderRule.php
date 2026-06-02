<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Domain\Models;

use App\Modules\Notifications\Infrastructure\Database\Factories\ReminderRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A rule that fires a reminder a fixed number of hours before fulfillment.
 *
 * @property string $id
 * @property int $offset_hours
 * @property bool $is_active
 */
class ReminderRule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'reminder_rules';

    protected $fillable = ['offset_hours', 'is_active'];

    protected $casts = [
        'offset_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ReminderRuleFactory
    {
        return ReminderRuleFactory::new();
    }
}
