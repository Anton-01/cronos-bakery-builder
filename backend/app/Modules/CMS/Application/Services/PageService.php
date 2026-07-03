<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Application\DTO\PageBlockData;
use App\Modules\CMS\Application\DTO\PageData;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\PageBlock;
use App\Modules\CMS\Domain\Models\Section;
use App\Modules\CMS\Domain\Repositories\PageRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates CMS page use-cases: brand-scoped page CRUD plus management of
 * the ordered builder blocks that compose each page.
 */
final readonly class PageService
{
    public function __construct(private PageRepositoryInterface $pages)
    {
    }

    /**
     * @return Collection<int, Page>
     */
    public function all(?int $brandId = null): Collection
    {
        return $this->pages->allForBrand($brandId);
    }

    /**
     * @return Collection<int, Page>
     */
    public function publishedPages(?int $brandId = null): Collection
    {
        return $this->pages->publishedPages($brandId);
    }

    public function publicBySlug(string $slug, ?int $brandId = null): ?Page
    {
        return $this->pages->findPublishedBySlug($slug, $brandId);
    }

    public function get(int $id): Page
    {
        /** @var Page $page */
        $page = $this->pages->findOrFail($id);
        $page->load(['blocks.section', 'brand']);

        return $page;
    }

    public function create(PageData $data): Page
    {
        return DB::transaction(function () use ($data): Page {
            /** @var Page $page */
            $page = $this->pages->create($this->withPublishTimestamp($data->toAttributes()));

            foreach ($data->blocks ?? [] as $position => $blockData) {
                $attributes = $blockData->toAttributes();
                $attributes['type'] = $this->resolveBlockType($blockData);
                $attributes['position'] = $position;

                $page->blocks()->create($attributes);
            }

            return $page->load(['blocks.section', 'brand']);
        });
    }

    public function update(int $id, PageData $data): Page
    {
        /** @var Page $page */
        $page = $this->pages->update($id, $this->withPublishTimestamp($data->toAttributes()));

        return $page->load(['blocks.section', 'brand']);
    }

    public function delete(int $id): void
    {
        $this->pages->delete($id);
    }

    public function publish(int $id): Page
    {
        /** @var Page $page */
        $page = $this->pages->update($id, [
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ]);

        return $page->load(['blocks.section', 'brand']);
    }

    public function unpublish(int $id): Page
    {
        /** @var Page $page */
        $page = $this->pages->update($id, ['status' => PageStatus::Draft->value]);

        return $page->load(['blocks.section', 'brand']);
    }

    public function addBlock(int $pageId, PageBlockData $data): PageBlock
    {
        /** @var Page $page */
        $page = $this->pages->findOrFail($pageId);

        $attributes = $data->toAttributes();
        $attributes['type'] = $this->resolveBlockType($data);
        $attributes['position'] = $data->position ?: $this->nextPosition($page);

        return $page->blocks()->create($attributes);
    }

    public function updateBlock(int $pageId, int $blockId, PageBlockData $data): PageBlock
    {
        $block = $this->findBlock($pageId, $blockId);

        $attributes = $data->toAttributes();
        $attributes['type'] = $this->resolveBlockType($data);

        $block->update($attributes);

        return $block->refresh();
    }

    public function removeBlock(int $pageId, int $blockId): void
    {
        $this->findBlock($pageId, $blockId)->delete();
    }

    /**
     * Replace the page's builder state atomically with the given ordered list:
     * blocks with an id are updated, blocks without one are created, and any
     * persisted block missing from the list is deleted. Positions follow the
     * array order.
     *
     * @param  array<int, PageBlockData>  $blocks
     */
    public function syncBlocks(int $pageId, array $blocks): Page
    {
        /** @var Page $page */
        $page = $this->pages->findOrFail($pageId);

        DB::transaction(function () use ($page, $blocks): void {
            $keptIds = [];

            foreach (array_values($blocks) as $position => $blockData) {
                $attributes = $blockData->toAttributes();
                $attributes['type'] = $this->resolveBlockType($blockData);
                $attributes['position'] = $position;

                if ($blockData->id !== null) {
                    $block = $this->findBlock($page->id, $blockData->id);
                    $block->update($attributes);
                } else {
                    $block = $page->blocks()->create($attributes);
                }

                $keptIds[] = $block->id;
            }

            $page->blocks()->whereNotIn('id', $keptIds)->delete();
        });

        return $page->refresh()->load(['blocks.section', 'brand']);
    }

    /**
     * Persist a new ordering for a page's blocks.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorderBlocks(int $pageId, array $orderedIds): void
    {
        DB::transaction(function () use ($pageId, $orderedIds): void {
            foreach ($orderedIds as $position => $blockId) {
                PageBlock::query()
                    ->where('page_id', $pageId)
                    ->where('id', $blockId)
                    ->update(['position' => $position]);
            }
        });
    }

    private function findBlock(int $pageId, int $blockId): PageBlock
    {
        return PageBlock::query()
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
        return (int) $page->blocks()->max('position') + 1;
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
