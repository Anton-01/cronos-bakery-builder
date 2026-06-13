<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Presentation\Http\Requests\StoreUserRequest;
use App\Modules\Administration\Presentation\Http\Requests\SuspendUserRequest;
use App\Modules\Administration\Presentation\Http\Requests\UpdateUserRequest;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * Full CRUD and lifecycle management for customer accounts.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%' . $request->query('search') . '%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })->when($request->filled('status'), function ($q) use ($request) {
                return match ($request->query('status')) {
                    'suspended' => $q->where('is_suspended', true),
                    'active' => $q->where('is_suspended', false),
                    default => $q,
                };
            })
            ->when($request->filled('role'), function ($q) use ($request): void {
                $q->where('role', $request->query('role'));
            })
            ->latest()->paginate((int) $request->query('per_page', '15'));

        return UserResource::collection($users);
    }

    public function show(string $user): UserResource
    {
        return new UserResource(User::query()->findOrFail($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, string $user): UserResource
    {
        $model = User::query()->findOrFail($user);
        $model->update($request->validated());

        return new UserResource($model->fresh());
    }

    public function destroy(string $user): JsonResponse
    {
        $model = User::query()->findOrFail($user);
        $model->tokens()->delete();
        $model->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function suspend(SuspendUserRequest $request, string $user): UserResource
    {
        $model = User::query()->findOrFail($user);
        $admin = $request->user();

        $model->suspend(
            $request->validated('reason'),
            $request->filled('suspended_until') ? Carbon::parse($request->validated('suspended_until')) : null,
            $admin->id,
        );

        $model->tokens()->delete();

        return new UserResource($model->fresh());
    }

    public function reactivate(string $user): UserResource
    {
        $model = User::query()->findOrFail($user);
        $model->reactivate();

        return new UserResource($model->fresh());
    }

    public function impersonate(string $user): JsonResponse
    {
        $model = User::query()->findOrFail($user);

        if ($model->is_suspended) {
            return response()->json(['message' => 'Cannot impersonate a suspended user.'], 422);
        }

        $token = $model->createToken('impersonation', ['impersonated'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($model),
        ]);
    }

    public function revokeSessions(string $user): JsonResponse
    {
        $model = User::query()->findOrFail($user);
        $model->tokens()->delete();

        DB::table('sessions')
            ->where('user_id', $model->id)
            ->delete();

        return response()->json(['message' => 'All sessions revoked.']);
    }

    public function sendPasswordReset(string $user): JsonResponse
    {
        $model = User::query()->findOrFail($user);

        $status = Password::broker()->sendResetLink(['email' => $model->email]);

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Password reset link sent.'
                : 'Unable to send reset link.',
            'status' => $status,
        ]);
    }
}
