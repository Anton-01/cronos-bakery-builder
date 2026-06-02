<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label->value,
            'label_text' => $this->label->label(),
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'notes' => $this->notes,
            'is_default' => $this->is_default,
        ];
    }
}
