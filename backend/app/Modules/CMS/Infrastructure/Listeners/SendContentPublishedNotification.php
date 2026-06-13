<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Listeners;

use App\Modules\CMS\Domain\Events\ContentPublished;
use App\Modules\Notifications\Infrastructure\Jobs\SendNotificationJob;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendContentPublishedNotification implements ShouldQueue
{
    public string $queue = 'email-notifications';

    public function handle(ContentPublished $event): void
    {
        $content = $event->content;
        $title = $content->title ?? $content->getKey();

        $log = NotificationLog::create([
            'recipient' => $this->resolveRecipientEmail($event->approverId),
            'subject' => "Content Published: {$title}",
            'body' => "The content \"{$title}\" has been successfully published.",
            'status' => 'pending',
        ]);

        SendNotificationJob::dispatch($log->id)->onQueue('email-notifications');
    }

    private function resolveRecipientEmail(int $userId): string
    {
        return \App\Modules\Authentication\Domain\Models\User::query()
            ->where('id', $userId)
            ->value('email') ?? '';
    }
}
