<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain\Models;

use App\Modules\Administration\Infrastructure\Database\Factories\AdminFactory;
use App\Modules\Administration\Infrastructure\Notifications\AdminResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Administrator identity. Authenticated through the dedicated `admin` guard and
 * authorised via Spatie roles & permissions registered on that same guard.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * Bind Spatie's permission resolution to the admin guard.
     */
    protected string $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'notification_settings',
        'is_active',
        'two_factor_secret',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notification_settings' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Whether two-factor authentication is enabled and confirmed.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminResetPasswordNotification($token));
    }

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}
