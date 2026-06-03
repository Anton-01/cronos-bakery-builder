<?php

declare(strict_types=1);

use App\Modules\Administration\Presentation\Http\Middleware\EnsureAdmin;
use App\Modules\Administration\Presentation\Http\Middleware\LogAdminActivity;
use App\Shared\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the load balancer / CDN / WAF in front of the app.
        $middleware->trustProxies(at: '*');

        // Enable Sanctum's SPA authentication for stateful, cookie-based requests.
        $middleware->statefulApi();

        // Hardening headers (WAF-ready) on every response.
        $middleware->append(SecurityHeaders::class);

        // Audit every mutating admin action (self-filters inside the middleware).
        $middleware->appendToGroup('api', LogAdminActivity::class);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
