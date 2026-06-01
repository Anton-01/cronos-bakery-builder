<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\DTO\UpdateProfileData;
use App\Modules\Authentication\Application\Services\ProfileService;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Presentation\Http\Requests\ChangePasswordRequest;
use App\Modules\Authentication\Presentation\Http\Requests\UpdateProfileRequest;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles)
    {
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
}
