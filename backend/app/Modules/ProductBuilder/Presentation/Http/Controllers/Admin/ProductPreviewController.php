<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\PreviewTokenService;
use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductPreviewController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $service,
        private readonly PreviewTokenService $tokens,
    ) {
    }

    /**
     * Admin-only: mints a short-lived token so the product can be viewed on
     * the public storefront (new tab) even while in draft.
     */
    public function generateToken(int $product): JsonResponse
    {
        $this->service->get($product); // 404 if the product does not exist

        return response()->json([
            'data' => [
                'token' => $this->tokens->mint($product),
                'expires_in_minutes' => PreviewTokenService::TTL_MINUTES,
            ],
        ]);
    }

    /**
     * Public: full configuration of the previewed product; the token is the
     * only credential and expires automatically.
     */
    public function show(string $token): ProductResource|JsonResponse
    {
        $productId = $this->tokens->resolve($token);

        if ($productId === null) {
            return response()->json(['message' => 'Preview token expired or invalid.'], 403);
        }

        return new ProductResource($this->service->get($productId));
    }
}
