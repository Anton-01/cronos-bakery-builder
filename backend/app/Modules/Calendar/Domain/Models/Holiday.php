<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use App\Modules\Calendar\Infrastructure\Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A festive full-day closure. Recurring holidays match month/day every year.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $name
 * @property bool $is_recurring
 */
class Holiday extends Model
{
    use HasFactory;

    protected $table = 'calendar_holidays';

    protected $fillable = ['date', 'name', 'is_recurring'];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    protected static function newFactory(): HolidayFactory
    {
        return HolidayFactory::new();
    }
}
