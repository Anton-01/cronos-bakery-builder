<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Resources;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class CatalogProductResource extends JsonResource
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
            'url' => "/pastel/{$this->slug}",
            'description' => $this->description,
            'image' => $this->image,
            'price' => [
                'amount' => $this->price_amount,
                'currency' => $this->currency,
            ],
            'seo' => [
                'meta_title' => $this->meta_title ?? $this->name,
                'meta_description' => $this->meta_description,
            ],
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
            ])->values()),
            'collections' => $this->whenLoaded('collections', fn () => $this->collections->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
            ])->values()),
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(fn ($v) => [
                'attribute_code' => $v->attribute?->code,
                'attribute_name' => $v->attribute?->name,
                'label' => $v->label,
                'value' => $v->value,
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('slug')),
        ];
    }
}
