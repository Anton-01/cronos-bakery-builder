<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Listeners;

use App\Modules\Catalog\Domain\Events\ProductCreated;
use App\Modules\Catalog\Infrastructure\Jobs\SyncProductToSearchIndex;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Reacts to {@see ProductCreated} by dispatching the search-indexing job.
 * Queued so catalog writes stay fast.
 */
class IndexProductOnCreation implements ShouldQueue
{
    public function handle(ProductCreated $event): void
    {
        SyncProductToSearchIndex::dispatch($event->product);
    }
}
