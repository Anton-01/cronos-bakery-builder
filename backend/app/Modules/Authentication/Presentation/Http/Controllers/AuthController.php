<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\DTO\LoginData;
use App\Modules\Authentication\Application\Services\AuthService;
use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Presentation\Http\Requests\LoginRequest;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(LoginData::fromArray($request->validated()), $request);

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->auth->logout($user);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
