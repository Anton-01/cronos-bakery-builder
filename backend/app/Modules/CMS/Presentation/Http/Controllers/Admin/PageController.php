<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\DTO\PageData;
use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Presentation\Http\Requests\StorePageRequest;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return PageResource::collection($this->pages->all());
    }

    public function show(string $page): PageResource
    {
        return new PageResource($this->pages->get($page));
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $page = $this->pages->create(PageData::fromArray($request->validated()));

        return (new PageResource($page))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StorePageRequest $request, string $page): PageResource
    {
        return new PageResource($this->pages->update($page, PageData::fromArray($request->validated())));
    }

    public function destroy(string $page): JsonResponse
    {
        $this->pages->delete($page);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
