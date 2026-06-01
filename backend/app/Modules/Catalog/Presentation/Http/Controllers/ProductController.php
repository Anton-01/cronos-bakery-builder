<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Modules\Catalog\Application\DTO\ProductData;
use App\Modules\Catalog\Application\Services\ProductService;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Thin HTTP adapter for the Catalog module. All orchestration is delegated to
 * {@see ProductService}; the controller only translates HTTP <-> application.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->products->list());
    }

    public function show(string $product): ProductResource
    {
        return new ProductResource($this->products->get($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create(ProductData::fromArray($request->validated()));

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function destroy(string $product): JsonResponse
    {
        $this->authorize('delete', Product::findOrFail($product));

        $this->products->delete($product);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
