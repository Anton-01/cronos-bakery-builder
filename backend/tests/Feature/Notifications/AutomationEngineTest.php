<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Modules\Notifications\Application\Services\TemplateRenderer;
use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Notifications\Infrastructure\Jobs\SendNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_renderer_substitutes_variables(): void
    {
        $rendered = app(TemplateRenderer::class)->render(
            'Hola {{ customer_name }}, pedido {{order_number}}.',
            ['customer_name' => 'Ana', 'order_number' => 'CR-1'],
        );

        $this->assertSame('Hola Ana, pedido CR-1.', $rendered);
    }

    public function test_a_triggered_event_with_an_active_template_queues_a_notification(): void
    {
        Queue::fake();
        NotificationTemplate::factory()->event(NotificationEvent::OrderPlaced)->create([
            'subject' => 'Pedido {{ order_number }}',
            'body' => 'Hola {{ customer_name }}',
        ]);

        AutomationTriggered::dispatch('order.placed', [
            'order_number' => 'CR-99', 'customer_name' => 'Ana',
        ], 'ana@cronos.test');

        $log = NotificationLog::query()->firstOrFail();
        $this->assertSame('Pedido CR-99', $log->subject);
        $this->assertSame('queued', $log->status);
        $this->assertSame('ana@cronos.test', $log->recipient);
        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_no_template_means_no_notification(): void
    {
        Queue::fake();

        AutomationTriggered::dispatch('order.placed', [], 'ana@cronos.test');

        $this->assertSame(0, NotificationLog::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_an_inactive_template_is_skipped(): void
    {
        Queue::fake();
        NotificationTemplate::factory()->event(NotificationEvent::OrderPlaced)->inactive()->create();

        AutomationTriggered::dispatch('order.placed', [], 'ana@cronos.test');

        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_dedupe_key_prevents_duplicate_notifications(): void
    {
        Queue::fake();
        NotificationTemplate::factory()->event(NotificationEvent::OrderReminder)->create();

        AutomationTriggered::dispatch('order.reminder', ['hours' => 24], 'ana@cronos.test', 'order.reminder:1:24');
        AutomationTriggered::dispatch('order.reminder', ['hours' => 24], 'ana@cronos.test', 'order.reminder:1:24');

        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_the_send_job_delivers_and_marks_the_log_sent(): void
    {
        Mail::fake();
        $log = NotificationLog::create([
            'event' => 'order.placed', 'recipient' => 'ana@cronos.test',
            'subject' => 'Hi', 'body' => '<p>Hi</p>', 'status' => 'queued',
        ]);

        (new SendNotificationJob($log->id))->handle();

        Mail::assertSent(\App\Modules\Notifications\Infrastructure\Mail\AutomationMail::class);
        $this->assertSame('sent', $log->refresh()->status);
        $this->assertNotNull($log->sent_at);
    }
}
