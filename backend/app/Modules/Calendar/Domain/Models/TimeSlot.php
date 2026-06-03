<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use App\Modules\Calendar\Infrastructure\Database\Factories\TimeSlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A bookable delivery/pickup window with its own capacity.
 *
 * @property string $id
 * @property string $label
 * @property string $start_time
 * @property string|null $end_time
 * @property int $capacity
 * @property bool $is_active
 * @property int $position
 */
class TimeSlot extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'calendar_time_slots';

    protected $fillable = ['label', 'start_time', 'end_time', 'capacity', 'is_active', 'position'];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @param  Builder<TimeSlot>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function newFactory(): TimeSlotFactory
    {
        return TimeSlotFactory::new();
    }
}
