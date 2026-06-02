<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Modules\Administration\Domain\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Authentication use-cases for administrators: credential verification, token
 * issuance/revocation and password recovery via the dedicated `admins` broker.
 */
final class AdminAuthService
{
    private const BROKER = 'admins';

    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    /**
     * @return array{admin: Admin, token: string}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, ?string $code = null): array
    {
        /** @var Admin|null $admin */
        $admin = Admin::query()->where('email', $email)->first();

        if ($admin === null || ! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $admin->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This administrator account is disabled.'],
            ]);
        }

        // Enforce the second factor when enabled.
        if ($admin->hasTwoFactorEnabled()) {
            if ($code === null || $code === '') {
                throw ValidationException::withMessages([
                    'code' => ['A two-factor code is required.'],
                ])->status(423);
            }

            if (! $this->twoFactor->verify($admin, $code)) {
                throw ValidationException::withMessages(['code' => ['The two-factor code is invalid.']]);
            }
        }

        $token = $admin->createToken('admin', ['admin'])->plainTextToken;

        return ['admin' => $admin, 'token' => $token];
    }

    public function logout(Admin $admin): void
    {
        $admin->currentAccessToken()?->delete();
    }

    /**
     * @throws ValidationException
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
     * @throws ValidationException
     */
    public function reset(array $credentials): void
    {
        $status = Password::broker(self::BROKER)->reset(
            $credentials,
            function (Admin $admin, string $password): void {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($admin));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }
    }
}
