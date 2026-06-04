<?php

declare(strict_types=1);

use App\Modules\Administration\Presentation\Http\Middleware\EnsureAdmin;
use App\Modules\Administration\Presentation\Http\Middleware\LogAdminActivity;
use App\Shared\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->append(SecurityHeaders::class);

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

        // Never expose stack traces or internal details to API consumers.
        $exceptions->respond(function (JsonResponse $response, \Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return $response;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            $body = ['message' => $e instanceof HttpExceptionInterface
                ? $e->getMessage()
                : 'Server Error'];

            if ($status === 422 && method_exists($e, 'errors')) {
                $body['errors'] = $e->errors();
            }

            return new JsonResponse($body, $status);
        });
    })->create();
