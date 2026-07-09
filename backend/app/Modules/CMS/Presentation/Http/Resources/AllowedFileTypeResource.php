<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\AllowedFileType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AllowedFileType
 */
class AllowedFileTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'mime_types' => $this->mime_types,
            'extensions' => $this->extensions,
            'icon_reference' => $this->icon_reference,
            'is_active' => $this->is_active,
        ];
    }
}
