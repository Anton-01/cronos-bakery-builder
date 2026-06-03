<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\Services\PasswordResetService;
use App\Modules\Authentication\Presentation\Http\Requests\ForgotPasswordRequest;
use App\Modules\Authentication\Presentation\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords)
    {
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->sendResetLink($request->validated('email'));

        return response()->json([
            'message' => 'If the email exists, a reset link has been sent.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwords->reset($request->validated());

        return response()->json(['message' => 'Password has been reset.']);
    }
}
