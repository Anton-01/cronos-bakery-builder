<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Application\Services;

use App\Modules\Authentication\Application\DTO\RegisterData;
use App\Modules\Authentication\Domain\Enums\Role;
use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Auth\Events\Registered;

/**
 * Handles customer self-registration: persists the account, dispatches the
 * Registered event (which triggers email verification) and issues an API token.
 */
final class RegistrationService
{
    /**
     * @return array{user: User, token: string}
     */
    public function register(RegisterData $data): array
    {
        $user = User::create([
            ...$data->toAttributes(),
            'role' => Role::Customer->value,
        ]);

        event(new Registered($user));

        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
