<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Notifications\Application\Services\ReminderService;
use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Notifications\Domain\Models\ReminderRule;
use App\Modules\Orders\Domain\Enums\FulfillmentType;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-09-01 10:00:00'));
        Queue::fake();
        NotificationTemplate::factory()->event(NotificationEvent::OrderReminder)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pickupOrder(string $date, string $time): Order
    {
        return Order::factory()->create([
            'user_id' => User::factory()->create()->id,
            'fulfillment_type' => FulfillmentType::Pickup->value,
            'status' => OrderStatus::Confirmed->value,
            'pickup_date' => $date,
            'pickup_time' => $time,
        ]);
    }

    public function test_a_reminder_fires_within_the_offset_window(): void
    {
        ReminderRule::factory()->create(['offset_hours' => 24]);
        // Pickup exactly 24h from "now".
        $this->pickupOrder('2025-09-02', '10:00');

        $count = app(ReminderService::class)->dispatchDue();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('notification_logs', ['event' => 'order.reminder']);
    }

    public function test_reminders_are_not_sent_outside_the_window(): void
    {
        ReminderRule::factory()->create(['offset_hours' => 2]);
        // Pickup is days away — the 2h reminder must not fire yet.
        $this->pickupOrder('2025-09-05', '10:00');

        $this->assertSame(0, app(ReminderService::class)->dispatchDue());
        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_reminders_are_idempotent_across_runs(): void
    {
        ReminderRule::factory()->create(['offset_hours' => 24]);
        $this->pickupOrder('2025-09-02', '10:00');

        $service = app(ReminderService::class);
        $service->dispatchDue();
        $service->dispatchDue(); // second hourly run

        $this->assertSame(1, NotificationLog::query()->where('event', 'order.reminder')->count());
    }

    public function test_multiple_offset_rules_each_fire_once(): void
    {
        ReminderRule::factory()->create(['offset_hours' => 24]);
        ReminderRule::factory()->create(['offset_hours' => 2]);

        // One order due in 24h, another due in 2h.
        $this->pickupOrder('2025-09-02', '10:00');
        $this->pickupOrder('2025-09-01', '12:00');

        $this->assertSame(2, app(ReminderService::class)->dispatchDue());
    }
}
