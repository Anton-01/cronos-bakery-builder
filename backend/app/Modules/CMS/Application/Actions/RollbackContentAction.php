<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Actions;

use App\Modules\CMS\Domain\Models\ContentVersion;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class RollbackContentAction
{
    public function execute(Page $page, int $versionId, int $actorId): Page
    {
        $version = ContentVersion::query()
            ->where('id', $versionId)
            ->where('versionable_type', $page->getMorphClass())
            ->where('versionable_id', $page->getKey())
            ->firstOrFail();

        return DB::transaction(function () use ($page, $version, $actorId): Page {
            $payloadBefore = $page->toArray();

            $restorePayload = $version->payload_before ?? $version->payload_after;

            $fillable = array_intersect_key($restorePayload, array_flip($page->getFillable()));
            $page->update($fillable);

            $nextVersion = ContentVersion::query()
                    ->where('versionable_type', $page->getMorphClass())
                    ->where('versionable_id', $page->getKey())
                    ->max('version_number') + 1;

            ContentVersion::create([
                'versionable_type' => $page->getMorphClass(),
                'versionable_id' => $page->getKey(),
                'version_number' => $nextVersion,
                'payload_before' => $payloadBefore,
                'payload_after' => $page->fresh()->toArray(),
                'status_before' => $payloadBefore['status'] ?? null,
                'status_after' => $page->status->value,
                'change_summary' => "Rollback to version #{$version->version_number}",
                'author_id' => $actorId,
            ]);

            Cache::tags(['pages', 'cms'])->flush();

            return $page->refresh();
        });
    }
}
