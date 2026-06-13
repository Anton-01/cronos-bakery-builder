<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Actions;

use App\Modules\CMS\Domain\Enums\ContentStatus;
use App\Modules\CMS\Domain\Events\ContentPublished;
use App\Modules\CMS\Domain\Events\ContentRejected;
use App\Modules\CMS\Domain\Events\ContentSubmittedForReview;
use App\Modules\CMS\Domain\Models\ContentVersion;
use App\Modules\CMS\Domain\Models\ContentWorkflow;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class PublishContentAction
{
    public function submitForReview(Page $page, int $requesterId, ?string $comment = null): ContentWorkflow
    {
        return $this->transition(
            content: $page,
            targetStatus: ContentStatus::PendingReview,
            actorId: $requesterId,
            comment: $comment,
        );
    }

    public function approve(Page $page, int $approverId, ?string $comment = null): ContentWorkflow
    {
        $scheduledAt = $page->scheduled_at;

        $targetStatus = ($scheduledAt !== null && $scheduledAt->isFuture())
            ? ContentStatus::Scheduled
            : ContentStatus::Published;

        return $this->transition(
            content: $page,
            targetStatus: $targetStatus,
            actorId: $approverId,
            approverId: $approverId,
            comment: $comment,
        );
    }

    public function reject(Page $page, int $reviewerId, ?string $reason = null): ContentWorkflow
    {
        return $this->transition(
            content: $page,
            targetStatus: ContentStatus::Draft,
            actorId: $reviewerId,
            comment: $reason,
        );
    }

    public function schedule(Page $page, \DateTimeInterface $publishAt, int $requesterId): ContentWorkflow
    {
        $page->scheduled_at = $publishAt;

        return $this->transition(
            content: $page,
            targetStatus: ContentStatus::Scheduled,
            actorId: $requesterId,
            approverId: $requesterId,
            scheduledAt: $publishAt,
        );
    }

    private function transition(
        Page $content,
        ContentStatus $targetStatus,
        int $actorId,
        ?int $approverId = null,
        ?string $comment = null,
        ?\DateTimeInterface $scheduledAt = null,
    ): ContentWorkflow {
        $currentStatus = ContentStatus::tryFrom($content->status->value)
            ?? ContentStatus::from($content->getRawOriginal('status'));

        if (! $currentStatus->canTransitionTo($targetStatus)) {
            throw new \DomainException(
                "Cannot transition from [{$currentStatus->value}] to [{$targetStatus->value}]."
            );
        }

        if ($targetStatus->requiresApproval() && $approverId === null) {
            throw new \DomainException(
                "Transition to [{$targetStatus->value}] requires an approver with admin privileges."
            );
        }

        return DB::transaction(function () use ($content, $currentStatus, $targetStatus, $actorId, $approverId, $comment, $scheduledAt): ContentWorkflow {
            $payloadBefore = $content->getOriginal();

            $updateData = ['status' => $targetStatus->value];

            if ($targetStatus === ContentStatus::Published && $content->published_at === null) {
                $updateData['published_at'] = now();
            }

            if ($scheduledAt !== null) {
                $updateData['scheduled_at'] = $scheduledAt;
            }

            $content->update($updateData);

            $nextVersion = ContentVersion::query()
                    ->where('versionable_type', $content->getMorphClass())
                    ->where('versionable_id', $content->getKey())
                    ->max('version_number') + 1;

            ContentVersion::create([
                'versionable_type' => $content->getMorphClass(),
                'versionable_id' => $content->getKey(),
                'version_number' => $nextVersion,
                'payload_before' => $payloadBefore,
                'payload_after' => $content->fresh()->toArray(),
                'status_before' => $currentStatus->value,
                'status_after' => $targetStatus->value,
                'change_summary' => $comment ?? "Status changed to {$targetStatus->label()}",
                'author_id' => $actorId,
            ]);

            $workflow = ContentWorkflow::create([
                'workflowable_type' => $content->getMorphClass(),
                'workflowable_id' => $content->getKey(),
                'from_status' => $currentStatus->value,
                'to_status' => $targetStatus->value,
                'requested_by' => $actorId,
                'approved_by' => $approverId,
                'comment' => $comment,
                'scheduled_at' => $scheduledAt,
            ]);

            $this->dispatchEvent($content, $targetStatus, $currentStatus, $actorId, $approverId, $workflow);

            if ($targetStatus === ContentStatus::Published) {
                Cache::tags(['pages', 'cms'])->flush();
            }

            return $workflow;
        });
    }

    private function dispatchEvent(
        Page $content,
        ContentStatus $targetStatus,
        ContentStatus $fromStatus,
        int $actorId,
        ?int $approverId,
        ContentWorkflow $workflow,
    ): void {
        match ($targetStatus) {
            ContentStatus::Published, ContentStatus::Scheduled => ContentPublished::dispatch(
                $content,
                $approverId ?? $actorId,
                $workflow->id,
            ),
            ContentStatus::PendingReview => ContentSubmittedForReview::dispatch(
                $content,
                $actorId,
                $workflow->id,
            ),
            ContentStatus::Draft => $fromStatus === ContentStatus::PendingReview
                ? ContentRejected::dispatch($content, $actorId, $workflow->id, $workflow->comment)
                : null,
            default => null,
        };
    }
}
