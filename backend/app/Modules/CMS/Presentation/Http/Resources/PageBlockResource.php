<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\PageBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PageBlock
 */
class PageBlockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resolvedType()->value,
            'config' => $this->resolvedData(),
            'position' => $this->position,
            'is_active' => $this->is_active,
            'section_id' => $this->section_id,
        ];
    }
}
