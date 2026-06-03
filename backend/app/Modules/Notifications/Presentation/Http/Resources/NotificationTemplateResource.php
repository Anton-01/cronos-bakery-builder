<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Resources;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationTemplate
 */
class NotificationTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = NotificationEvent::tryFrom($this->event);

        return [
            'id' => $this->id,
            'event' => $this->event,
            'event_label' => $event?->label() ?? $this->event,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body' => $this->body,
            'variables' => $event?->variables() ?? [],
            'is_active' => $this->is_active,
        ];
    }
}
