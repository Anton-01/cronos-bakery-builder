<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Models\ProductOptionLink;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductOptionLinkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'uuid', 'exists:pb_option_templates,id'],
            'legend' => ['nullable', 'string'],
            'enabled_value_ids' => ['nullable', 'array'],
            'enabled_value_ids.*' => ['uuid'],
            'position' => ['integer'],
        ]);

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

    public function update(Request $request, Product $product, ProductOptionLink $link): ProductOptionLinkResource
    {
        $data = $request->validate([
            'legend' => ['nullable', 'string'],
            'enabled_value_ids' => ['nullable', 'array'],
            'enabled_value_ids.*' => ['uuid'],
            'position' => ['integer'],
        ]);

        $link->update($data);
        $link->load('template.values');

        return new ProductOptionLinkResource($link);
    }

    public function destroy(Product $product, ProductOptionLink $link): JsonResponse
    {
        $link->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
