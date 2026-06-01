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

    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->model->newQuery()
            ->published()
            ->with(['sections' => fn ($query) => $query->where('is_active', true), 'sections.section'])
            ->where('slug', $slug)
            ->first();
    }

    public function publishedPages(): Collection
    {
        return $this->model->newQuery()
            ->published()
            ->orderBy('title')
            ->get();
    }
}
