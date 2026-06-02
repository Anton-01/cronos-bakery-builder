<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Modules\Administration\Domain\Models\Admin;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP-based two-factor authentication for administrators (RFC 6238).
 */
final class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa)
    {
    }

    /**
     * Generate and store a fresh (unconfirmed) secret, returning the secret and
     * an otpauth:// URI for QR provisioning.
     *
     * @return array{secret: string, otpauth_url: string}
     */
    public function generate(Admin $admin): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        $issuer = (string) Config::get('app.name', 'Cronos');
        $url = $this->google2fa->getQRCodeUrl($issuer, $admin->email, $secret);

        return ['secret' => $secret, 'otpauth_url' => $url];
    }

    /**
     * Confirm enrolment by verifying the first code.
     *
     * @throws ValidationException
     */
    public function confirm(Admin $admin, string $code): void
    {
        if ($admin->two_factor_secret === null) {
            throw ValidationException::withMessages(['code' => ['Two-factor is not pending.']]);
        }

        if (! $this->verify($admin, $code)) {
            throw ValidationException::withMessages(['code' => ['The code is invalid.']]);
        }

        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
    }

    public function disable(Admin $admin): void
    {
        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Verify a TOTP code against the admin's secret (with a small window).
     */
    public function verify(Admin $admin, string $code): bool
    {
        if ($admin->two_factor_secret === null) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($admin->two_factor_secret, $code, 1);
    }
}
