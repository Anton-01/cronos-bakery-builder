<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_slug' => $this->product_slug,
            'configuration' => $this->configuration,
            'unit_price' => $this->unit_price_amount,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total_amount,
        ];
    }
}
