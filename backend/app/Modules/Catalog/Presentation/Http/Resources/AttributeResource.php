<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Resources;

use App\Modules\Catalog\Domain\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attribute
 */
class AttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'is_filterable' => $this->is_filterable,
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label,
                'value' => $v->value,
                'metadata' => $v->metadata,
            ])->values()),
        ];
    }
}
