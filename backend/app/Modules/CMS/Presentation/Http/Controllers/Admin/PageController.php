<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\DTO\PageData;
use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Presentation\Http\Requests\StorePageRequest;
use App\Modules\CMS\Presentation\Http\Requests\UpdatePageRequest;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $brandId = $request->filled('brand_id') ? (int) $request->query('brand_id') : null;

        return PageResource::collection($this->pages->all($brandId));
    }

    public function show(int $page): PageResource
    {
        return new PageResource($this->pages->get($page));
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $page = $this->pages->create(PageData::fromArray($request->validated()));

        return (new PageResource($page))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(UpdatePageRequest $request, int $page): PageResource
    {
        return new PageResource($this->pages->update($page, PageData::fromArray($request->validated())));
    }

    public function destroy(int $page): JsonResponse
    {
        $this->pages->delete($page);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function publish(int $page): PageResource
    {
        return new PageResource($this->pages->publish($page));
    }

    public function unpublish(int $page): PageResource
    {
        return new PageResource($this->pages->unpublish($page));
    }
}
