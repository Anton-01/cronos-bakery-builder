<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Presentation\Http\Requests\UpdateAdminPasswordRequest;
use App\Modules\Administration\Presentation\Http\Requests\UpdateAdminProfileRequest;
use App\Modules\Administration\Presentation\Http\Requests\UpdateNotificationSettingsRequest;
use App\Modules\Administration\Presentation\Http\Requests\UploadAvatarRequest;
use App\Modules\Administration\Presentation\Http\Resources\AdminResource;
use App\Shared\Application\Services\AvatarService;
use App\Shared\Http\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Self-service account management for the authenticated administrator:
 * personal data, avatar (MinIO), password, notification preferences and
 * active Sanctum sessions (devices).
 */
class AdminProfileController extends Controller
{
    public function __construct(private readonly AvatarService $avatars)
    {
    }

    public function update(UpdateAdminProfileRequest $request): AdminResource
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->update($request->validated());

        return new AdminResource($admin->refresh());
    }

    public function uploadAvatar(UploadAvatarRequest $request): AdminResource
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $path = $this->avatars->replace($request->file('avatar'), $admin->avatar);
        $admin->update(['avatar' => $path]);

        return new AdminResource($admin->refresh());
    }

    public function deleteAvatar(Request $request): AdminResource
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $this->avatars->delete($admin->avatar);
        $admin->update(['avatar' => null]);

        return new AdminResource($admin->refresh());
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! Hash::check($request->validated('current_password'), $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $admin->update(['password' => $request->validated('password')]);

        // Kill every OTHER session; the current device stays signed in.
        $admin->tokens()->whereKeyNot($admin->currentAccessToken()?->id)->delete();

        return response()->json(['message' => 'Contraseña actualizada. Las demás sesiones fueron cerradas.']);
    }

    public function updateNotificationSettings(UpdateNotificationSettingsRequest $request): AdminResource
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->update(['notification_settings' => $request->validated('settings')]);

        return new AdminResource($admin->refresh());
    }

    // --- Devices (Sanctum sessions) -------------------------------------------

    public function sessions(Request $request): AnonymousResourceCollection
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        return SessionResource::collection($tokens);
    }

    public function revokeSession(Request $request, int $token): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if ((int) $admin->currentAccessToken()?->id === $token) {
            return response()->json(
                ['message' => 'Usa "Cerrar sesión" para terminar la sesión actual.'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $deleted = $admin->tokens()->whereKey($token)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Sesión no encontrada.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(['message' => 'Sesión revocada.']);
    }

    public function revokeOtherSessions(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->tokens()->whereKeyNot($admin->currentAccessToken()?->id)->delete();

        return response()->json(['message' => 'Se cerraron las demás sesiones.']);
    }
}
