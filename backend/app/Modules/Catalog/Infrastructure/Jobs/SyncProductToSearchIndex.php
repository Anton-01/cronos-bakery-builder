<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Jobs;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job that (re)indexes a product for search. Demonstrates the queue/job
 * pipeline; the actual search integration is wired up in a later phase.
 */
class SyncProductToSearchIndex implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Product $product)
    {
    }

    public function handle(): void
    {
        // Placeholder for search-index integration (e.g. Meilisearch/Elastic).
        Log::info('Product queued for search indexing', [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
        ]);
    }
}
