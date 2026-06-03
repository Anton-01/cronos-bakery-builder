<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\DTO\PageBlockData;
use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Presentation\Http\Requests\PageBlockRequest;
use App\Modules\CMS\Presentation\Http\Requests\ReorderBlocksRequest;
use App\Modules\CMS\Presentation\Http\Resources\PageSectionResource;
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

    public function store(PageBlockRequest $request, string $page): JsonResponse
    {
        $block = $this->pages->addBlock($page, PageBlockData::fromArray($request->validated()));

        return (new PageSectionResource($block))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(PageBlockRequest $request, string $page, string $block): PageSectionResource
    {
        return new PageSectionResource(
            $this->pages->updateBlock($page, $block, PageBlockData::fromArray($request->validated())),
        );
    }

    public function destroy(string $page, string $block): JsonResponse
    {
        $this->pages->removeBlock($page, $block);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function reorder(ReorderBlocksRequest $request, string $page): JsonResponse
    {
        $this->pages->reorderBlocks($page, $request->validated('order'));

        return response()->json(['message' => 'Blocks reordered.']);
    }
}
