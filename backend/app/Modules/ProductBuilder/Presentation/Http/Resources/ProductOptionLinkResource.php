<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Resources;

use App\Modules\ProductBuilder\Domain\Models\ProductOptionLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductOptionLink
 */
class ProductOptionLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'template_id' => $this->template_id,
            'legend' => $this->legend,
            'enabled_value_ids' => $this->enabled_value_ids,
            'position' => $this->position,
            'template' => new OptionTemplateResource($this->whenLoaded('template')),
        ];
    }
}
