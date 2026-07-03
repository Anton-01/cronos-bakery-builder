<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\DTO\PageBlockData;
use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Presentation\Http\Requests\PageBlockRequest;
use App\Modules\CMS\Presentation\Http\Requests\ReorderBlocksRequest;
use App\Modules\CMS\Presentation\Http\Requests\SyncPageBlocksRequest;
use App\Modules\CMS\Presentation\Http\Resources\PageBlockResource;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Manages the ordered builder blocks that compose a page.
 */
class PageBlockController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function store(PageBlockRequest $request, int $page): JsonResponse
    {
        $block = $this->pages->addBlock($page, PageBlockData::fromArray($request->validated()));

        return (new PageBlockResource($block))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(PageBlockRequest $request, int $page, int $block): PageBlockResource
    {
        return new PageBlockResource(
            $this->pages->updateBlock($page, $block, PageBlockData::fromArray($request->validated())),
        );
    }

    public function destroy(int $page, int $block): JsonResponse
    {
        $this->pages->removeBlock($page, $block);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Bulk save from the page builder: replaces the page's block state with
     * the ordered list carried by the request.
     */
    public function sync(SyncPageBlocksRequest $request, int $page): PageResource
    {
        $blocks = array_map(
            static fn (array $block): PageBlockData => PageBlockData::fromArray($block),
            $request->validated('blocks'),
        );

        return new PageResource($this->pages->syncBlocks($page, $blocks));
    }

    public function reorder(ReorderBlocksRequest $request, int $page): JsonResponse
    {
        $this->pages->reorderBlocks(
            $page,
            array_map(intval(...), $request->validated('order')),
        );

        return response()->json(['message' => 'Blocks reordered.']);
    }
}
