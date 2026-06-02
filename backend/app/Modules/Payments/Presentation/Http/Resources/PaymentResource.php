<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Resources;

use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'gateway' => $this->gateway->value,
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'attempts' => $this->attempts,
            'checkout' => $this->metadata,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'events' => PaymentEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
