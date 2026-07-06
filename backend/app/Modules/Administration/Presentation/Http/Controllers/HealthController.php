<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Observability endpoints: a lightweight health check for load balancers and an
 * operational metrics snapshot for monitoring.
 */
class HealthController extends Controller
{
    /**
     * Liveness/readiness probe — checks the critical dependencies.
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'cache' => $this->check(function (): bool {
                Cache::set('health:ping', '1', 5);

                return Cache::get('health:ping') === '1';
            }),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Operational metrics snapshot (admin-only) for dashboards/alerting.
     */
    public function metrics(): JsonResponse
    {
        return response()->json([
            'data' => [
                'queue' => [
                    'connection' => config('queue.default'),
                    'pending' => $this->safe(fn (): int => Queue::size()),
                ],
                'orders' => [
                    'total' => Order::query()->count(),
                ],
                'payments' => [
                    'total' => Transaction::query()->count(),
                    'paid' => Transaction::query()->where('status', 'paid')->count(),
                ],
                'notifications' => [
                    'sent' => NotificationLog::query()->where('status', 'sent')->count(),
                    'failed' => NotificationLog::query()->where('status', 'failed')->count(),
                ],
            ],
        ]);
    }

    private function check(callable $probe): bool
    {
        try {
            return (bool) $probe();
        } catch (Throwable) {
            return false;
        }
    }

    private function safe(callable $probe): int
    {
        try {
            return (int) $probe();
        } catch (Throwable) {
            return -1;
        }
    }
}
