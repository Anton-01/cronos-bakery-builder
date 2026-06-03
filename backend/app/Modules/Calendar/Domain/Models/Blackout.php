<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use App\Modules\Calendar\Infrastructure\Database\Factories\BlackoutFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An ad-hoc block. A null time_slot_id blocks the whole day; otherwise only the
 * referenced slot on that date.
 *
 * @property string $id
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $time_slot_id
 * @property string|null $reason
 */
class Blackout extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'calendar_blackouts';

    protected $fillable = ['date', 'time_slot_id', 'reason'];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function newFactory(): BlackoutFactory
    {
        return BlackoutFactory::new();
    }
}
