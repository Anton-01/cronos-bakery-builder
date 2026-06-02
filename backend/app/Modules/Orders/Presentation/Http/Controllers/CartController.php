<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Modules\Orders\Application\Services\CartService;
use App\Modules\Orders\Presentation\Http\Requests\AddToCartRequest;
use App\Modules\Orders\Presentation\Http\Requests\UpdateCartItemRequest;
use App\Modules\Orders\Presentation\Http\Resources\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts)
    {
    }

    public function show(Request $request): CartResource
    {
        return new CartResource($this->carts->forUser($request->user()));
    }

    public function store(AddToCartRequest $request): CartResource
    {
        $this->carts->addItem(
            $request->user(),
            $request->validated('product_slug'),
            $request->validated('selections'),
            (int) ($request->validated('quantity') ?? 1),
        );

        return new CartResource($this->carts->forUser($request->user()));
    }

    public function update(UpdateCartItemRequest $request, string $item): CartResource
    {
        $this->carts->updateQuantity($request->user(), $item, (int) $request->validated('quantity'));

        return new CartResource($this->carts->forUser($request->user()));
    }

    public function destroyItem(Request $request, string $item): CartResource
    {
        $this->carts->removeItem($request->user(), $item);

        return new CartResource($this->carts->forUser($request->user()));
    }

    public function clear(Request $request): JsonResponse
    {
        $this->carts->clear($request->user());

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
