<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Resources;

use App\Modules\CMS\Domain\Models\ContentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentWorkflow
 */
class ContentWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status->value,
            'to_status' => $this->to_status->value,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'comment' => $this->comment,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'requester_name' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'approver_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
