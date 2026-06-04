<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Resources;

use App\Modules\ProductBuilder\Domain\Models\OptionValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OptionValue
 */
class OptionValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'value' => $this->value,
            'price_modifier_type' => $this->price_modifier_type->value,
            'price_modifier_amount' => $this->price_modifier_amount,
            'metadata' => $this->metadata,
            'is_default' => $this->is_default,
            'position' => $this->position,
        ];
    }
}
