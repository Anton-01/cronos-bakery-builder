<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MenuItem
 */
class MenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'url' => $this->url,
            'target' => $this->target,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
