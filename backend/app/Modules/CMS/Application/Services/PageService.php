<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Application\DTO\PageBlockData;
use App\Modules\CMS\Application\DTO\PageData;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\PageSection;
use App\Modules\CMS\Domain\Models\Section;
use App\Modules\CMS\Domain\Repositories\PageRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates CMS page use-cases: page CRUD plus management of the ordered
 * builder blocks that compose each page.
 */
final readonly class PageService
{
    public function __construct(private PageRepositoryInterface $pages)
    {
    }

    public function all(): Collection
    {
        return $this->pages->all();
    }

    public function publishedPages(): Collection
    {
        return $this->pages->publishedPages();
    }

    public function publicBySlug(string $slug): ?Page
    {
        return $this->pages->findPublishedBySlug($slug);
    }

    public function get(string $id): Page
    {
        /** @var Page $page */
        $page = $this->pages->findOrFail($id);
        $page->load(['sections.section']);

        return $page;
    }

    public function create(PageData $data): Page
    {
        /** @var Page $page */
        $page = $this->pages->create($this->withPublishTimestamp($data->toAttributes()));

        return $page;
    }

    public function update(string $id, PageData $data): Page
    {
        /** @var Page $page */
        $page = $this->pages->update($id, $this->withPublishTimestamp($data->toAttributes()));

        return $page->load(['sections.section']);
    }

    public function delete(string $id): void
    {
        $this->pages->delete($id);
    }

    public function addBlock(string $pageId, PageBlockData $data): PageSection
    {
        /** @var Page $page */
        $page = $this->pages->findOrFail($pageId);

        $attributes = $data->toAttributes();
        $attributes['type'] = $this->resolveBlockType($data);
        $attributes['position'] = $data->position ?: $this->nextPosition($page);

        return $page->sections()->create($attributes);
    }

    public function updateBlock(string $pageId, string $blockId, PageBlockData $data): PageSection
    {
        $block = $this->findBlock($pageId, $blockId);

        $attributes = $data->toAttributes();
        $attributes['type'] = $this->resolveBlockType($data);

        $block->update($attributes);

        return $block->refresh();
    }

    public function removeBlock(string $pageId, string $blockId): void
    {
        $this->findBlock($pageId, $blockId)->delete();
    }

    /**
     * Persist a new ordering for a page's blocks.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderBlocks(string $pageId, array $orderedIds): void
    {
        DB::transaction(function () use ($pageId, $orderedIds): void {
            foreach ($orderedIds as $position => $blockId) {
                PageSection::query()
                    ->where('page_id', $pageId)
                    ->where('id', $blockId)
                    ->update(['position' => $position]);
            }
        });
    }

    private function findBlock(string $pageId, string $blockId): PageSection
    {
        return PageSection::query()
            ->where('page_id', $pageId)
            ->where('id', $blockId)
            ->firstOrFail();
    }

    /**
     * A referenced reusable section dictates the block type; otherwise the
     * inline type is used.
     */
    private function resolveBlockType(PageBlockData $data): string
    {
        if ($data->sectionId !== null) {
            return Section::query()->findOrFail($data->sectionId)->type->value;
        }

        return (string) $data->type;
    }

    private function nextPosition(Page $page): int
    {
        return (int) $page->sections()->max('position') + 1;
    }

    /**
     * Stamp published_at when a page is published without an explicit timestamp.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withPublishTimestamp(array $attributes): array
    {
        if (($attributes['status'] ?? null) === PageStatus::Published->value
            && empty($attributes['published_at'])) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }
}
