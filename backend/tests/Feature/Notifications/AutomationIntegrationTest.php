<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Application\Services\ReconciliationService;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_payment_triggers_the_payment_approved_automation(): void
    {
        Queue::fake();
        NotificationTemplate::factory()->event(NotificationEvent::PaymentApproved)->create([
            'subject' => 'Pago de {{ order_number }}',
            'body' => 'Gracias {{ customer_name }}',
        ]);

        $order = Order::factory()->create();
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create(['order_id' => $order->id]);

        app(ReconciliationService::class)->applyStatus($payment, PaymentStatus::Paid, 'reconciled');

        $this->assertDatabaseHas('notification_logs', [
            'event' => 'payment.approved',
            'recipient' => $order->user->email,
        ]);
    }

    public function test_reconciliation_dispatches_the_automation_event(): void
    {
        Event::fake([AutomationTriggered::class]);

        $order = Order::factory()->create();
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create(['order_id' => $order->id]);

        app(ReconciliationService::class)->applyStatus($payment, PaymentStatus::Paid, 'reconciled');

        Event::assertDispatched(AutomationTriggered::class, fn ($e) => $e->event === 'payment.approved');
    }
}
