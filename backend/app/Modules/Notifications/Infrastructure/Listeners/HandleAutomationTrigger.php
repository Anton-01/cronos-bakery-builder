<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Listeners;

use App\Modules\Notifications\Application\Services\TemplateRenderer;
use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Notifications\Infrastructure\Jobs\SendNotificationJob;

/**
 * Resolves the active template for a triggered automation event, renders it and
 * queues delivery. Idempotent per dedupe key (used by reminders).
 */
class HandleAutomationTrigger
{
    public function __construct(private readonly TemplateRenderer $renderer)
    {
    }

    public function handle(AutomationTriggered $event): void
    {
        $template = NotificationTemplate::query()
            ->where('event', $event->event)
            ->where('is_active', true)
            ->first();

        // No active template configured for this event — nothing to send.
        if ($template === null) {
            return;
        }

        // Idempotency: never send the same keyed notification twice.
        if ($event->dedupeKey !== null
            && NotificationLog::query()->where('dedupe_key', $event->dedupeKey)->exists()) {
            return;
        }

        $log = NotificationLog::create([
            'event' => $event->event,
            'recipient' => $event->recipient,
            'subject' => $this->renderer->render($template->subject, $event->context),
            'body' => $this->renderer->render($template->body, $event->context),
            'status' => 'queued',
            'dedupe_key' => $event->dedupeKey,
            'context' => $event->context,
        ]);

        SendNotificationJob::dispatch($log->id);
    }
}
