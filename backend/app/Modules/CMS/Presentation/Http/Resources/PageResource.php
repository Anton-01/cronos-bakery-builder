<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Page
 */
class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'content' => $this->content,
            'seo' => [
                'meta_title' => $this->meta_title ?? $this->title,
                'meta_description' => $this->meta_description,
            ],
            'published_at' => $this->published_at?->toIso8601String(),
            'sections' => PageSectionResource::collection($this->whenLoaded('sections')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
