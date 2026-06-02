<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'fulfillment' => [
                'type' => $this->fulfillment_type->value,
                'type_label' => $this->fulfillment_type->label(),
                'shipping_address' => $this->shipping_address,
                'branch' => $this->whenLoaded('branch', fn () => $this->branch
                    ? new BranchResource($this->branch)
                    : null),
                'pickup_date' => $this->pickup_date?->toDateString(),
                'pickup_time' => $this->pickup_time,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'subtotal' => $this->subtotal_amount,
                'total' => $this->total_amount,
                'currency' => $this->currency,
            ],
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }
}
