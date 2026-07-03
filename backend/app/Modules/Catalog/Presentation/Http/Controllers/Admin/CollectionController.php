<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\Services\TaxonomyService;
use App\Modules\Catalog\Presentation\Http\Requests\StoreCollectionRequest;
use App\Modules\Catalog\Presentation\Http\Resources\CollectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class CollectionController extends Controller
{
    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return CollectionResource::collection($this->taxonomy->collections());
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = $this->taxonomy->createCollection($request->toAttributes());

        return (new CollectionResource($collection))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreCollectionRequest $request, int $collection): CollectionResource
    {
        return new CollectionResource($this->taxonomy->updateCollection($collection, $request->toAttributes()));
    }

    public function destroy(int $collection): JsonResponse
    {
        $this->taxonomy->deleteCollection($collection);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
