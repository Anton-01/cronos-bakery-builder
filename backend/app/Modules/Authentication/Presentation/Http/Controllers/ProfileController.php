<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\DTO\UpdateProfileData;
use App\Modules\Authentication\Application\Services\ProfileService;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Presentation\Http\Requests\ChangePasswordRequest;
use App\Modules\Authentication\Presentation\Http\Requests\UpdateProfileRequest;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use App\Shared\Application\Services\AvatarService;
use App\Shared\Http\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly AvatarService $avatars,
    ) {
    }

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        $updated = $this->profiles->update($user, UpdateProfileData::fromArray($request->validated()));

        return new UserResource($updated);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->profiles->changePassword(
            $user,
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return response()->json(['message' => 'Password updated. Please sign in again.']);
    }

    public function uploadAvatar(Request $request): UserResource
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $path = $this->avatars->replace($request->file('avatar'), $user->avatar);
        $user->update(['avatar' => $path]);

        return new UserResource($user->refresh());
    }

    public function deleteAvatar(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->avatars->delete($user->avatar);
        $user->update(['avatar' => null]);

        return new UserResource($user->refresh());
    }

    public function updateNotificationSettings(Request $request): UserResource
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['notification_settings' => $validated['settings']]);

        return new UserResource($user->refresh());
    }

    // --- Devices (Sanctum sessions) -------------------------------------------

    public function sessions(Request $request): AnonymousResourceCollection
    {
        return SessionResource::collection(
            $request->user()->tokens()->orderByDesc('last_used_at')->orderByDesc('created_at')->get(),
        );
    }

    public function revokeSession(Request $request, int $token): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ((int) $user->currentAccessToken()?->id === $token) {
            return response()->json(['message' => 'Use logout to end the current session.'], 422);
        }

        $deleted = $user->tokens()->whereKey($token)->delete();

        return $deleted === 0
            ? response()->json(['message' => 'Session not found.'], 404)
            : response()->json(['message' => 'Session revoked.']);
    }
}
