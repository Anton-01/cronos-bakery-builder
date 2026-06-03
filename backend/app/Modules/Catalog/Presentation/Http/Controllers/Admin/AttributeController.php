<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\Services\TaxonomyService;
use App\Modules\Catalog\Presentation\Http\Requests\StoreAttributeRequest;
use App\Modules\Catalog\Presentation\Http\Requests\StoreAttributeValueRequest;
use App\Modules\Catalog\Presentation\Http\Resources\AttributeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AttributeController extends Controller
{
    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return AttributeResource::collection($this->taxonomy->attributes());
    }

    public function store(StoreAttributeRequest $request): JsonResponse
    {
        $attribute = $this->taxonomy->createAttribute($request->toAttributes());

        return (new AttributeResource($attribute))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreAttributeRequest $request, string $attribute): AttributeResource
    {
        return new AttributeResource($this->taxonomy->updateAttribute($attribute, $request->toAttributes()));
    }

    public function destroy(string $attribute): JsonResponse
    {
        $this->taxonomy->deleteAttribute($attribute);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function storeValue(StoreAttributeValueRequest $request, string $attribute): JsonResponse
    {
        $this->taxonomy->addAttributeValue($attribute, $request->toAttributes());

        return response()->json(
            new AttributeResource($this->taxonomy->attributes()->firstWhere('id', $attribute)),
            JsonResponse::HTTP_CREATED,
        );
    }

    public function destroyValue(string $attribute, string $value): JsonResponse
    {
        $this->taxonomy->deleteAttributeValue($attribute, $value);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
