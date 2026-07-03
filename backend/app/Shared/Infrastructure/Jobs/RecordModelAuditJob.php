<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Jobs;

use App\Shared\Domain\Models\ModelAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Persists one audit-trail entry in the background so the write never adds
 * latency (or a failure mode) to the admin's request. All context is captured
 * as scalars by {@see \App\Shared\Infrastructure\Observers\AuditObserver}
 * before dispatch — the job is independent of request state.
 */
final class RecordModelAuditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function __construct(
        public readonly string $event,
        public readonly string $auditableType,
        public readonly int $auditableId,
        public readonly ?int $brandId,
        public readonly ?int $userId,
        public readonly ?array $oldValues,
        public readonly ?array $newValues,
        public readonly ?string $ipAddress,
    ) {
    }

    public function handle(): void
    {
        ModelAuditLog::create([
            'event' => $this->event,
            'auditable_type' => $this->auditableType,
            'auditable_id' => $this->auditableId,
            'brand_id' => $this->brandId,
            'user_id' => $this->userId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
