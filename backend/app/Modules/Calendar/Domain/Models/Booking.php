<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reservation consumed against day/slot capacity (e.g. by a placed order).
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property int|null $time_slot_id
 * @property int $quantity
 * @property string|null $reference
 */
class Booking extends Model
{
    protected $table = 'calendar_bookings';

    protected $fillable = ['date', 'time_slot_id', 'quantity', 'reference'];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];
}
