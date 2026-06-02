<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Jobs;

use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Infrastructure\Mail\AutomationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a prepared notification asynchronously and records the outcome,
 * providing scalable, queue-backed delivery.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $logId)
    {
    }

    public function handle(): void
    {
        $log = NotificationLog::query()->find($this->logId);

        if ($log === null || $log->status === 'sent') {
            return;
        }

        Mail::to($log->recipient)->send(new AutomationMail($log->subject, $log->body));

        $log->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(\Throwable $e): void
    {
        NotificationLog::query()->where('id', $this->logId)->update(['status' => 'failed']);
    }
}
