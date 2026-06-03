<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Application\Services;

use App\Modules\Authentication\Application\DTO\UpdateProfileData;
use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Customer self-service profile operations: updating personal details and
 * changing the account password.
 */
final class ProfileService
{
    public function update(User $user, UpdateProfileData $data): User
    {
        $user->update($data->toAttributes());

        return $user->refresh();
    }

    /**
     * @throws ValidationException when the current password does not match.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if ($user->password === null || ! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => $newPassword]);

        // Revoke other sessions/tokens after a password change for safety.
        $user->tokens()->delete();
    }
}
