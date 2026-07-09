<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Calendar\Application\Services\CalendarService;
use App\Modules\Calendar\Domain\Models\Blackout;
use App\Modules\Calendar\Domain\Models\Holiday;
use App\Modules\Calendar\Domain\Models\ProductionRule;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityEngineTest extends TestCase
{
    use RefreshDatabase;

    private function openEveryDay(int $capacity = 0): void
    {
        for ($w = 0; $w <= 6; $w++) {
            ScheduleDay::factory()->create(['weekday' => $w, 'is_open' => true, 'capacity' => $capacity]);
        }
    }

    private function service(): CalendarService
    {
        return app(CalendarService::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time to a Monday 08:00 for deterministic scheduling.
        Carbon::setTestNow(Carbon::parse('2025-09-01 08:00:00')); // 2025-09-01 is a Monday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_minimum_date_respects_the_production_lead_time(): void
    {
        $this->openEveryDay();
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 48]);

        $min = $this->service()->minimumDate(null);

        // 48h from Mon 08:00 = Wed 08:00 → first slot Wed 10:00.
        $this->assertSame('2025-09-03', $min['date']);
        $this->assertSame('2025-09-03T10:00', $min['at']);
    }

    public function test_engine_skips_closed_days(): void
    {
        // Open only Friday (weekday 5).
        for ($w = 0; $w <= 6; $w++) {
            ScheduleDay::factory()->create(['weekday' => $w, 'is_open' => $w === 5, 'capacity' => 0]);
        }
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);

        // 24h from Mon = Tue, but next open day is Friday 2025-09-05.
        $this->assertSame('2025-09-05', $this->service()->minimumDate(null)['date']);
    }

    public function test_engine_skips_holidays(): void
    {
        $this->openEveryDay();
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);
        Holiday::factory()->create(['date' => '2025-09-02']); // Tue is a holiday

        // 24h → Tue, but Tue is a holiday → Wed.
        $this->assertSame('2025-09-03', $this->service()->minimumDate(null)['date']);
    }

    public function test_engine_skips_full_day_blackouts(): void
    {
        $this->openEveryDay();
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);
        Blackout::factory()->create(['date' => '2025-09-02', 'time_slot_id' => null]);

        $this->assertSame('2025-09-03', $this->service()->minimumDate(null)['date']);
    }

    public function test_a_blacked_out_slot_is_removed_but_others_remain(): void
    {
        $this->openEveryDay();
        $morning = TimeSlot::factory()->create(['label' => 'AM', 'start_time' => '10:00', 'capacity' => 5, 'position' => 0]);
        TimeSlot::factory()->create(['label' => 'PM', 'start_time' => '15:00', 'capacity' => 5, 'position' => 1]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);
        Blackout::factory()->create(['date' => '2025-09-02', 'time_slot_id' => $morning->id]);

        $min = $this->service()->minimumDate(null);
        // Morning slot on Tue is blocked → first available is the PM slot.
        $this->assertSame('2025-09-02', $min['date']);
        $this->assertSame('2025-09-02T15:00', $min['at']);
    }

    public function test_slot_capacity_is_exhausted_by_bookings(): void
    {
        $this->openEveryDay();
        $slot = TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 2]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);

        // Fully book the Tuesday slot.
        $this->service()->reserve(Carbon::parse('2025-09-02'), $slot->id, 2);

        // Tue is full → next day Wed.
        $this->assertSame('2025-09-03', $this->service()->minimumDate(null)['date']);
    }

    public function test_day_capacity_caps_availability(): void
    {
        // Day capacity of 1 across all weekdays.
        $this->openEveryDay(capacity: 1);
        $slot = TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 10]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);

        $this->service()->reserve(Carbon::parse('2025-09-02'), $slot->id, 1);

        // Tue day capacity reached even though the slot itself has room.
        $this->assertSame('2025-09-03', $this->service()->minimumDate(null)['date']);
    }

    public function test_per_product_rule_overrides_the_default(): void
    {
        $this->openEveryDay();
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        $product = Product::factory()->create();
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 24]);
        ProductionRule::factory()->create(['product_id' => $product->id, 'lead_time_hours' => 168]);

        // Default → Tue; product with 7-day lead → next Monday 2025-09-08.
        $this->assertSame('2025-09-02', $this->service()->minimumDate(null)['date']);
        $this->assertSame('2025-09-08', $this->service()->minimumDate($product->id)['date']);
    }
}
