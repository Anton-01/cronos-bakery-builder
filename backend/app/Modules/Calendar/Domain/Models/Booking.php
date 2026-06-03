<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A reservation consumed against day/slot capacity (e.g. by a placed order).
 *
 * @property string $id
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $time_slot_id
 * @property int $quantity
 * @property string|null $reference
 */
class Booking extends Model
{
    use HasUuids;

    protected $table = 'calendar_bookings';

    protected $fillable = ['date', 'time_slot_id', 'quantity', 'reference'];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];
}
