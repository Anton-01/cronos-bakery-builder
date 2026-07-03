<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observers;

use App\Shared\Infrastructure\Jobs\RecordModelAuditJob;
use Illuminate\Database\Eloquent\Model;

/**
 * Translates Eloquent lifecycle events into audit-trail jobs. The observer
 * runs inside the request, where the acting admin and IP are still known; it
 * captures plain values and defers the database write to a queued job that is
 * only dispatched after the surrounding transaction commits.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', null, $this->auditableValues($model, $model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->auditableValues($model, $model->getChanges());

        if ($changes === []) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);

        $this->record($model, 'updated', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        // For soft-deletable models the "restored" / final delete pair tells
        // the full story; a plain delete records the last known state.
        $this->record($model, 'deleted', $this->auditableValues($model, $model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', null, $this->auditableValues($model, $model->getAttributes()));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function record(Model $model, string $event, ?array $oldValues, ?array $newValues): void
    {
        RecordModelAuditJob::dispatch(
            event: $event,
            auditableType: $model->getMorphClass(),
            auditableId: (int) $model->getKey(),
            brandId: method_exists($model, 'auditBrandId') ? $model->auditBrandId() : null,
            userId: $this->actingAdminId(),
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: request()?->ip(),
        )->afterCommit();
    }

    /**
     * Strip attributes that must never reach the trail (hidden + excluded).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function auditableValues(Model $model, array $values): array
    {
        $excluded = array_merge(
            $model->getHidden(),
            method_exists($model, 'auditExclude') ? $model->auditExclude() : ['created_at', 'updated_at'],
        );

        return array_diff_key($values, array_flip($excluded));
    }

    private function actingAdminId(): ?int
    {
        $user = auth()->user();

        // Only panel administrators are recorded as actors; system processes
        // (jobs, seeders) and storefront customers leave a null user_id.
        if ($user instanceof \App\Modules\Administration\Domain\Models\Admin) {
            return (int) $user->getKey();
        }

        return null;
    }
}
