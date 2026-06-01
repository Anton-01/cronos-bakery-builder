<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Menu
 */
class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location->value,
            'is_active' => $this->is_active,
            'items' => MenuItemResource::collection($this->whenLoaded('rootItems')),
        ];
    }
}
