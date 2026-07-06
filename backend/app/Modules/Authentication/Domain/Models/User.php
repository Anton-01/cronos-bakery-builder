<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Domain\Models;

use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Authentication\Domain\Enums\Role;
use App\Modules\Authentication\Infrastructure\Database\Factories\UserFactory;
use App\Modules\Authentication\Infrastructure\Notifications\ResetPasswordNotification;
use App\Modules\Authentication\Infrastructure\Notifications\VerifyEmailNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Customer identity aggregate. Owns credentials, profile and linked social
 * accounts. Administrators are modelled separately ({@see \App\Modules\Administration\Domain\Models\Admin}).
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $avatar
 * @property string $name
 * @property Role $role
 * @property bool $is_suspended
 * @property \Carbon\Carbon|null $suspended_at
 * @property \Carbon\Carbon|null $suspended_until
 * @property string|null $suspension_reason
 * @property int|null $suspended_by
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'brand_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'notification_settings',
        'role',
        'is_suspended',
        'suspended_at',
        'suspended_until',
        'suspension_reason',
        'suspended_by',
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
            'brand_id' => 'integer',
            'notification_settings' => 'array',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
        ];
    }

    /**
     * Full display name composed from the customer's first and last name.
     */
    protected function name(): Attribute
    {
        return Attribute::get(
            fn (): string => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /**
     * @return BelongsTo<\App\Modules\CMS\Domain\Models\Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CMS\Domain\Models\Brand::class);
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [Role::Staff, Role::Admin], true);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Determine whether this user is currently suspended.
     */
    public function isSuspended(): bool
    {
        if (! $this->is_suspended) {
            return false;
        }

        // If a temporary suspension has expired, the user is no longer suspended.
        if ($this->suspended_until !== null && $this->suspended_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Suspend this user account.
     */
    public function suspend(string $reason, ?Carbon $until, int $adminId): void
    {
        $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_until' => $until,
            'suspension_reason' => $reason,
            'suspended_by' => $adminId,
        ]);
    }

    /**
     * Reactivate a suspended user account.
     */
    public function reactivate(): void
    {
        $this->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_until' => null,
            'suspension_reason' => null,
            'suspended_by' => null,
        ]);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function suspendedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'suspended_by');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
