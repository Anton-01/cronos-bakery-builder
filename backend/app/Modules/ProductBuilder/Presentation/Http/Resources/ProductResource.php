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
            'discount_type' => $this->discount_type ?? 'none',
            'discount_value' => $this->discount_value ?? 0,
            'tax_class' => $this->tax_class ?? 'standard',
            'vat' => $this->vat ?? 16,
            'tags' => $this->tags,
            'is_active' => $this->is_active,
            'options_count' => $this->options_count ?? ($this->relationLoaded('options') ? $this->options->count() : 0),
            'gallery' => $this->whenLoaded('gallery', fn () => $this->gallery->map(fn ($img) => [
                'id' => $img->id,
                'path' => $img->path,
                'name' => $img->name,
                'alt_text' => $img->alt_text,
                'position' => $img->position,
            ])),
            'options' => OptionResource::collection($this->whenLoaded('options')),
            'rules' => OptionRuleResource::collection($this->whenLoaded('rules')),
        ];
    }
}
