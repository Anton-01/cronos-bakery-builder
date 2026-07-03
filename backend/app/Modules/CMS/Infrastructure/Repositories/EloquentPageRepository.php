<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Repositories;

use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Repositories\PageRepositoryInterface;
use App\Shared\Infrastructure\Repositories\AbstractEloquentRepository;
use Illuminate\Support\Collection;

/**
 * Eloquent-backed implementation of the Page repository contract.
 */
class EloquentPageRepository extends AbstractEloquentRepository implements PageRepositoryInterface
{
    protected function model(): string
    {
        return Page::class;
    }

    public function allForBrand(?int $brandId = null): Collection
    {
        return $this->model->newQuery()
            ->with('brand')
            ->when($brandId !== null, fn ($query) => $query->forBrand($brandId))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findPublishedBySlug(string $slug, ?int $brandId = null): ?Page
    {
        return $this->model->newQuery()
            ->published()
            ->with(['blocks' => fn ($query) => $query->where('is_active', true), 'blocks.section'])
            ->when($brandId !== null, fn ($query) => $query->forBrand($brandId))
            ->where('slug', $slug)
            ->first();
    }

    public function publishedPages(?int $brandId = null): Collection
    {
        return $this->model->newQuery()
            ->published()
            ->when($brandId !== null, fn ($query) => $query->forBrand($brandId))
            ->orderBy('title')
            ->get();
    }
}
