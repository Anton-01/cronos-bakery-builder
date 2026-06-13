<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Listeners;

use App\Modules\CMS\Domain\Events\ContentSubmittedForReview;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Infrastructure\Jobs\SendNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendContentReviewNotification implements ShouldQueue
{
    public string $queue = 'email-notifications';

    public function handle(ContentSubmittedForReview $event): void
    {
        $content = $event->content;
        $title = $content->title ?? $content->getKey();

        $admins = \App\Modules\Authentication\Domain\Models\User::query()
            ->where('role', 'admin')
            ->pluck('email');

        foreach ($admins as $email) {
            $log = NotificationLog::create([
                'recipient' => $email,
                'subject' => "Review Required: {$title}",
                'body' => "A new content item \"{$title}\" has been submitted for review and requires your approval.",
                'status' => 'pending',
            ]);

            SendNotificationJob::dispatch($log->id)->onQueue('email-notifications');
        }
    }
}
