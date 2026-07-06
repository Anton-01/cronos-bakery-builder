<?php

declare(strict_types=1);

namespace App\Shared\Domain\Models;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Sanctum token extended with client context (IP, User-Agent and a derived
 * human-readable device name). Powers the "Devices" self-service view and the
 * admin-side session audit: each row IS a session that can be revoked.
 *
 * @property int $id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_name
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'ip_address',
        'user_agent',
        'device_name',
    ];

    /**
     * Stamp the client context onto this token (called right after creation).
     */
    public function recordClientContext(Request $request): void
    {
        $userAgent = (string) $request->userAgent();

        $this->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr($userAgent, 0, 1024),
            'device_name' => self::deviceNameFrom($userAgent),
        ])->save();
    }

    /**
     * Best-effort human label from a User-Agent ("Chrome · Windows").
     */
    public static function deviceNameFrom(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Dispositivo desconocido';
        }

        $platform = match (true) {
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Otro',
        };

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Navegador',
        };

        return "{$browser} · {$platform}";
    }
}
