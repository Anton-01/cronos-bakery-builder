<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Models;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\AddressLabel;
use App\Modules\Orders\Infrastructure\Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved customer address (Casa / Trabajo / Otra).
 *
 * @property string $id
 * @property int $user_id
 * @property AddressLabel $label
 * @property string $recipient_name
 * @property string $line1
 * @property string $city
 * @property bool $is_default
 */
class Address extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'line1',
        'line2',
        'city',
        'state',
        'country',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'label' => AddressLabel::class,
        'is_default' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'label' => $this->label->value,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'notes' => $this->notes,
        ];
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
