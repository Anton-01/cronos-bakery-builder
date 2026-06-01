<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function __construct(private readonly ProductAdminService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->service->all());
    }

    public function show(string $product): ProductResource
    {
        return new ProductResource($this->service->get($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->createProduct($request->toAttributes());

        return (new ProductResource($product))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreProductRequest $request, string $product): ProductResource
    {
        return new ProductResource($this->service->updateProduct($product, $request->toAttributes()));
    }

    public function destroy(string $product): JsonResponse
    {
        $this->service->deleteProduct($product);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
