<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Middleware;

use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Domain\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every mutating administrative action for auditing. Runs in the API
 * group and self-filters: only admin-area, state-changing requests made by an
 * authenticated administrator are logged. Sensitive fields are redacted.
 */
class LogAdminActivity
{
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const REDACT = [
        'password', 'password_confirmation', 'current_password', 'token',
        'secret_key', 'webhook_secret', 'public_key', 'credentials',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            $this->record($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        return $request->is('api/admin/*')
            && in_array($request->method(), self::MUTATING, true)
            && $request->user() instanceof Admin;
    }

    private function record(Request $request, Response $response): void
    {
        /** @var Admin $admin */
        $admin = $request->user();

        AuditLog::create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'route_name' => $request->route()?->getName(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'payload' => $this->sanitize($request->all()),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach (self::REDACT as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }
}
