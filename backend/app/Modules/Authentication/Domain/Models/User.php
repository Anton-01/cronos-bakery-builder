<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Domain\Models;

use App\Modules\Authentication\Domain\Enums\Role;
use App\Modules\Authentication\Infrastructure\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Authentication aggregate root. Owns identity, credentials and role for every
 * actor in the system (customers, staff, administrators).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [Role::Staff, Role::Admin], true);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
