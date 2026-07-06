<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Models\ProductOptionLink;
use App\Modules\ProductBuilder\Presentation\Http\Requests\StoreProductOptionLinkRequest;
use App\Modules\ProductBuilder\Presentation\Http\Requests\UpdateProductOptionLinkRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductOptionLinkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ProductOptionLinkController extends Controller
{
    public function index(Product $product): AnonymousResourceCollection
    {
        $links = $product->optionLinks()
            ->with('template.values')
            ->orderBy('position')
            ->get();

        return ProductOptionLinkResource::collection($links);
    }

    public function store(StoreProductOptionLinkRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        // Prevent duplicate links.
        $exists = $product->optionLinks()
            ->where('template_id', $data['template_id'])
            ->exists();

        if ($exists) {
            return response()->json(
                ['message' => 'This template is already linked to the product.'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $link = $product->optionLinks()->create($data);
        $link->load('template.values');

        return (new ProductOptionLinkResource($link))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(UpdateProductOptionLinkRequest $request, Product $product, ProductOptionLink $link): ProductOptionLinkResource
    {
        $link->update($request->validated());
        $link->load('template.values');

        return new ProductOptionLinkResource($link);
    }

    public function destroy(Product $product, ProductOptionLink $link): JsonResponse
    {
        $link->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
