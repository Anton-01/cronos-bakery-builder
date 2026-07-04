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
            'excluded_value_ids' => $this->excluded_value_ids,
            'position' => $this->position,
            'template' => new OptionTemplateResource($this->whenLoaded('template')),
            // Template values with exclusions applied — what the storefront renders.
            'values' => $this->whenLoaded(
                'template',
                fn () => OptionTemplateValueResource::collection($this->effectiveValues()),
            ),
        ];
    }
}
