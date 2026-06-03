<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Resources;

use App\Modules\ProductBuilder\Domain\Models\OptionRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OptionRule
 */
class OptionRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_id' => $this->option_id,
            'depends_on_option_id' => $this->depends_on_option_id,
            'operator' => $this->operator->value,
            'value' => $this->value,
            'action' => $this->action->value,
            'position' => $this->position,
        ];
    }
}
