<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Jobs;

use App\Modules\CMS\Domain\Enums\ContentStatus;
use App\Modules\CMS\Domain\Events\ContentPublished;
use App\Modules\CMS\Domain\Models\ContentVersion;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublishScheduledContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $pages = Page::query()
            ->where('status', ContentStatus::Scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($pages as $page) {
            DB::transaction(function () use ($page): void {
                $payloadBefore = $page->toArray();

                $page->update([
                    'status' => ContentStatus::Published->value,
                    'published_at' => now(),
                ]);

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
                    'status_before' => ContentStatus::Scheduled->value,
                    'status_after' => ContentStatus::Published->value,
                    'change_summary' => 'Scheduled publication executed',
                    'author_id' => $page->last_editor_id ?? 1,
                ]);

                ContentPublished::dispatch($page, $page->last_editor_id ?? 1, '');
            });
        }

        if ($pages->isNotEmpty()) {
            Cache::tags(['pages', 'cms'])->flush();
        }
    }
}
