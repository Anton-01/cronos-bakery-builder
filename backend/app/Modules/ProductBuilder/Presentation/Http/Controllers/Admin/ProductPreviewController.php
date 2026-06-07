<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ProductPreviewController extends Controller
{
    public function __construct(private readonly ProductAdminService $service)
    {
    }

    public function generateToken(string $product): JsonResponse
    {
        $this->service->get($product);

        $token = Str::random(64);
        Cache::put("product_preview:{$token}", $product, now()->addMinutes(30));

        return response()->json(['data' => ['token' => $token]]);
    }

    public function show(string $token): ProductResource|JsonResponse
    {
        $productId = Cache::get("product_preview:{$token}");

        if (! $productId) {
            return response()->json(['message' => 'Preview token expired or invalid.'], 403);
        }

        return new ProductResource($this->service->get($productId));
    }
}
