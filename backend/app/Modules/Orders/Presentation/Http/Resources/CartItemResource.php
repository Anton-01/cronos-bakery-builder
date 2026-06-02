<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CartItem
 */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_slug' => $this->product_slug,
            'configuration' => $this->configuration,
            'unit_price' => ['amount' => $this->unit_price_amount, 'currency' => $this->currency],
            'quantity' => $this->quantity,
            'line_total' => ['amount' => $this->lineTotal(), 'currency' => $this->currency],
        ];
    }
}
