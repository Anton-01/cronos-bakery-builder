<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\ContentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentVersion
 */
class ContentVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'versionable_type' => $this->versionable_type,
            'versionable_id' => $this->versionable_id,
            'version_number' => $this->version_number,
            'payload_before' => $this->payload_before,
            'payload_after' => $this->payload_after,
            'status_before' => $this->status_before,
            'status_after' => $this->status_after,
            'change_summary' => $this->change_summary,
            'author_id' => $this->author_id,
            'author_name' => $this->whenLoaded('author', fn () => $this->author?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
