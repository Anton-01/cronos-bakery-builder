<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Resources;

use App\Modules\Notifications\Domain\Models\ReminderRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReminderRule
 */
class ReminderRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'offset_hours' => $this->offset_hours,
            'label' => $this->offset_hours . 'h antes',
            'is_active' => $this->is_active,
        ];
    }
}
