<?php

declare(strict_types=1);

namespace App\Modules\Orders\Domain\Models;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer's persistent cart (one per user).
 *
 * @property string $id
 * @property int $user_id
 */
class Cart extends Model
{
    use HasUuids;

    protected $table = 'carts';

    protected $fillable = ['user_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('created_at');
    }

    public function subtotalAmount(): int
    {
        return (int) $this->items->sum(fn (CartItem $item) => $item->lineTotal());
    }
}
