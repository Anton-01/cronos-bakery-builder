<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers;

use App\Modules\ProductBuilder\Application\Services\ConfiguratorService;
use App\Modules\ProductBuilder\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\ProductBuilder\Presentation\Http\Requests\QuoteRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Public configurator: lists configurable products, exposes a product's full
 * configuration and computes authoritative prices for selections.
 */
class ConfiguratorController extends Controller
{
    public function __construct(
        private readonly ConfiguratorService $configurator,
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->products->activeProducts());
    }

    public function show(string $slug): ProductResource
    {
        return new ProductResource($this->configurator->configuration($slug));
    }

    public function quote(QuoteRequest $request, string $slug): JsonResponse
    {
        $result = $this->configurator->quote($slug, $request->selections());

        return response()->json([
            'data' => [
                'product' => $result['product']->slug,
                'visible' => $result['visible'],
                'price' => $result['price'],
            ],
        ]);
    }
}
