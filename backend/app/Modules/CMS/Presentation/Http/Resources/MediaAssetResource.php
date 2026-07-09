<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @mixin MediaAsset
 */
class MediaAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'disk' => $this->disk,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'transformations' => $this->transformations,
            'processing_status' => $this->processing_status,
            'storage_provider_id' => $this->storage_provider_id,
            'uploaded_by' => $this->uploaded_by,
            'url' => $this->publicUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function publicUrl(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (Throwable) {
            return null; // Discos sin URL pública (ej. "local").
        }
    }
}
