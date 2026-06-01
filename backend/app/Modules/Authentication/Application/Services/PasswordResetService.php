<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Application\Services;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Wraps Laravel's password broker for the customer ("users") guard: sending
 * reset links and applying new passwords.
 */
final class PasswordResetService
{
    private const BROKER = 'users';

    /**
     * @throws ValidationException when the email cannot be processed.
     */
    public function sendResetLink(string $email): void
    {
        $status = Password::broker(self::BROKER)->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }
    }

    /**
     * @param  array{email: string, password: string, token: string}  $credentials
     *
     * @throws ValidationException when the token/credentials are invalid.
     */
    public function reset(array $credentials): void
    {
        $status = Password::broker(self::BROKER)->reset(
            $credentials,
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }
    }
}
