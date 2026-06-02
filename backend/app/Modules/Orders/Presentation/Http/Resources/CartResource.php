<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = $this->items->first()->currency ?? 'USD';
        $subtotal = $this->subtotalAmount();

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->items),
            'item_count' => $this->items->sum('quantity'),
            'summary' => [
                'subtotal' => ['amount' => $subtotal, 'currency' => $currency],
                'total' => ['amount' => $subtotal, 'currency' => $currency],
            ],
        ];
    }
}
