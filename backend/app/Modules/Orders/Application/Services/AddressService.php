<?php

declare(strict_types=1);

namespace App\Modules\Orders\Application\Services;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Models\Address;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages a customer's saved addresses (Casa / Trabajo / Otra), keeping at most
 * one default per user.
 */
final class AddressService
{
    /**
     * @return Collection<int, Address>
     */
    public function forUser(User $user): Collection
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes): Address {
            $attributes['user_id'] = $user->id;
            $isDefault = (bool) ($attributes['is_default'] ?? false);

            $address = Address::create($attributes);

            if ($isDefault || $this->forUser($user)->count() === 1) {
                $this->makeDefault($user, $address);
            }

            return $address->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, string $id, array $attributes): Address
    {
        $address = $this->find($user, $id);

        return DB::transaction(function () use ($user, $address, $attributes): Address {
            $address->update($attributes);

            if (($attributes['is_default'] ?? false) === true) {
                $this->makeDefault($user, $address);
            }

            return $address->refresh();
        });
    }

    public function delete(User $user, string $id): void
    {
        $this->find($user, $id)->delete();
    }

    public function find(User $user, string $id): Address
    {
        return Address::query()->where('user_id', $user->id)->whereKey($id)->firstOrFail();
    }

    private function makeDefault(User $user, Address $address): void
    {
        Address::query()->where('user_id', $user->id)->where('id', '!=', $address->id)
            ->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}
