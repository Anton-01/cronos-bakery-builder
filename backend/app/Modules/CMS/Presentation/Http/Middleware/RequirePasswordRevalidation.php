<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

final class RequirePasswordRevalidation
{
    private const SUDO_TTL_SECONDS = 900; // 15 minutes

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isWithinSudoWindow($request)) {
            return $next($request);
        }

        $password = $request->header('X-Sudo-Password');

        if ($password === null) {
            return new JsonResponse([
                'message' => 'This action requires password re-confirmation.',
                'sudo_required' => true,
            ], Response::HTTP_FORBIDDEN);
        }

        $user = $request->user();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return new JsonResponse([
                'message' => 'The provided password is incorrect.',
                'sudo_required' => true,
            ], Response::HTTP_FORBIDDEN);
        }

        $request->session()->put('sudo_verified_at', now()->timestamp);

        return $next($request);
    }

    private function isWithinSudoWindow(Request $request): bool
    {
        $verifiedAt = $request->session()->get('sudo_verified_at');

        if ($verifiedAt === null) {
            return false;
        }

        return (now()->timestamp - $verifiedAt) < self::SUDO_TTL_SECONDS;
    }
}
