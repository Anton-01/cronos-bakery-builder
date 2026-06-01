<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\DTO\SectionData;
use App\Modules\CMS\Application\Services\SectionService;
use App\Modules\CMS\Presentation\Http\Requests\StoreSectionRequest;
use App\Modules\CMS\Presentation\Http\Resources\SectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * CRUD for the reusable section library.
 */
class SectionController extends Controller
{
    public function __construct(private readonly SectionService $sections)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return SectionResource::collection($this->sections->all());
    }

    public function show(string $section): SectionResource
    {
        return new SectionResource($this->sections->get($section));
    }

    public function store(StoreSectionRequest $request): JsonResponse
    {
        $section = $this->sections->create(SectionData::fromArray($request->validated()));

        return (new SectionResource($section))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreSectionRequest $request, string $section): SectionResource
    {
        return new SectionResource($this->sections->update($section, SectionData::fromArray($request->validated())));
    }

    public function destroy(string $section): JsonResponse
    {
        $this->sections->delete($section);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
