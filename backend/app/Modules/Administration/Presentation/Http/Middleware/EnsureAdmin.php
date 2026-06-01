<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Middleware;

use App\Modules\Administration\Domain\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the administration panel. Runs after `auth:sanctum` and guarantees
 * the authenticated token belongs to an *active* administrator. It also binds
 * the resolved admin to the `admin` guard so Spatie's role/permission
 * middleware (which is guard-aware) authorises against admin-guard roles.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Admin || ! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Administrator access required.');
        }

        Auth::shouldUse('admin');
        Auth::guard('admin')->setUser($user);

        return $next($request);
    }
}
