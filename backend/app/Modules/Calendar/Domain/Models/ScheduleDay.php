<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use App\Modules\Calendar\Infrastructure\Database\Factories\ScheduleDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Opening configuration for a weekday plus its daily capacity (0 = unlimited).
 *
 * @property int $id
 * @property int $weekday
 * @property bool $is_open
 * @property int $capacity
 */
class ScheduleDay extends Model
{
    use HasFactory;

    protected $table = 'calendar_schedule_days';

    protected $fillable = ['weekday', 'is_open', 'capacity'];

    protected $casts = [
        'weekday' => 'integer',
        'is_open' => 'boolean',
        'capacity' => 'integer',
    ];

    protected static function newFactory(): ScheduleDayFactory
    {
        return ScheduleDayFactory::new();
    }
}
