<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Resources;

use App\Modules\Catalog\Domain\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'url' => "/categoria/{$this->slug}",
            'description' => $this->description,
            'image' => $this->image,
            'seo' => [
                'meta_title' => $this->meta_title ?? $this->name,
                'meta_description' => $this->meta_description,
            ],
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
