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
     * All pages, optionally scoped to one brand.
     *
     * @return Collection<int, Page>
     */
    public function allForBrand(?int $brandId = null): Collection;

    /**
     * Find a published page by slug, eager-loading its ordered blocks. The
     * lookup is brand-scoped when a brand id is given (slugs are only unique
     * per brand).
     */
    public function findPublishedBySlug(string $slug, ?int $brandId = null): ?Page;

    /**
     * @return Collection<int, Page>
     */
    public function publishedPages(?int $brandId = null): Collection;
}
