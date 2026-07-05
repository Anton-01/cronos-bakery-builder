<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Resources;

use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'order_id' => $this->order_id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order?->number),
            'payment_gateway_id' => $this->payment_gateway_id,
            'gateway_name' => $this->whenLoaded('gateway', fn () => $this->gateway?->name),
            'driver_name' => $this->whenLoaded('gateway', fn () => $this->gateway?->driver_name),
            'environment' => $this->whenLoaded('gateway', fn () => $this->gateway?->environment->value),
            'provider_transaction_id' => $this->provider_transaction_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'attempts' => $this->attempts,
            'checkout' => $this->checkout,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'events' => TransactionEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
