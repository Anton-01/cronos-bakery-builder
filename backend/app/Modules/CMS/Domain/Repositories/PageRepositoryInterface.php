<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Repositories;

use App\Modules\CMS\Domain\Models\Page;
use App\Shared\Domain\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Persistence contract for the Page aggregate.
 */
interface PageRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a published page by slug, eager-loading its ordered blocks.
     */
    public function findPublishedBySlug(string $slug): ?Page;

    /**
     * @return Collection<int, Page>
     */
    public function publishedPages(): Collection;
}
