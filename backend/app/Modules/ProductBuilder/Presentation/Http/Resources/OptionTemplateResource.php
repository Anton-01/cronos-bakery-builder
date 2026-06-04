<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Resources;

use App\Modules\ProductBuilder\Domain\Models\OptionTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OptionTemplate
 */
class OptionTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type->value,
            'help_text' => $this->help_text,
            'is_required' => $this->is_required,
            'position' => $this->position,
            'config' => $this->config,
            'values' => OptionTemplateValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
