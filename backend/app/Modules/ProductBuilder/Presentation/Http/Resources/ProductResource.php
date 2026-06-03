<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Resources;

use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'base_price' => [
                'amount' => $this->base_price_amount,
                'currency' => $this->currency,
            ],
            'is_active' => $this->is_active,
            'options' => OptionResource::collection($this->whenLoaded('options')),
            'rules' => OptionRuleResource::collection($this->whenLoaded('rules')),
        ];
    }
}
