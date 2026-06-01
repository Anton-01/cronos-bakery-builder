<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Application\Services\AdminAuthService;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Presentation\Http\Requests\AdminLoginRequest;
use App\Modules\Administration\Presentation\Http\Resources\AdminResource;
use App\Modules\Authentication\Presentation\Http\Requests\ForgotPasswordRequest;
use App\Modules\Authentication\Presentation\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $auth)
    {
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'admin' => new AdminResource($result['admin']),
            'token' => $result['token'],
        ]);
    }

    public function me(Request $request): AdminResource
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return new AdminResource($admin);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $this->auth->logout($admin);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendResetLink($request->validated('email'));

        return response()->json(['message' => 'If the email exists, a reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->reset($request->validated());

        return response()->json(['message' => 'Password has been reset.']);
    }
}
