<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Resources;

use App\Modules\Payments\Domain\Models\TransactionEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TransactionEvent
 */
class TransactionEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'signature_valid' => $this->signature_valid,
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
