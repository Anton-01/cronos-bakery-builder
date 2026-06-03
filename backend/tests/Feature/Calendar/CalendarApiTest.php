<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Calendar\Domain\Models\ProductionRule;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-09-01 08:00:00'));
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function openEveryDay(): void
    {
        for ($w = 0; $w <= 6; $w++) {
            ScheduleDay::factory()->create(['weekday' => $w, 'is_open' => true, 'capacity' => 0]);
        }
        TimeSlot::factory()->create(['start_time' => '10:00', 'capacity' => 5]);
        ProductionRule::factory()->create(['product_id' => null, 'lead_time_hours' => 48]);
    }

    private function actingAsCalendarAdmin(): void
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Production->value); // has "manage calendar"
        Sanctum::actingAs($admin);
    }

    public function test_public_availability_endpoint_returns_min_and_days(): void
    {
        $this->openEveryDay();

        $this->getJson('/api/calendar/availability?days=10')
            ->assertOk()
            ->assertJsonPath('data.lead_time_hours', 48)
            ->assertJsonPath('data.minimum.date', '2025-09-03')
            ->assertJsonStructure(['data' => ['minimum' => ['date', 'slot_id', 'at'], 'days']]);
    }

    public function test_an_admin_can_update_the_weekly_schedule(): void
    {
        $this->actingAsCalendarAdmin();

        $this->putJson('/api/admin/calendar/schedule', [
            'days' => [
                ['weekday' => 0, 'is_open' => false, 'capacity' => 0],
                ['weekday' => 1, 'is_open' => true, 'capacity' => 10],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('calendar_schedule_days', ['weekday' => 1, 'capacity' => 10]);
        $this->assertDatabaseHas('calendar_schedule_days', ['weekday' => 0, 'is_open' => false]);
    }

    public function test_an_admin_can_manage_slots_holidays_and_blackouts(): void
    {
        $this->actingAsCalendarAdmin();

        $slotId = $this->postJson('/api/admin/calendar/slots', [
            'label' => 'AM', 'start_time' => '09:00', 'capacity' => 4,
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/admin/calendar/holidays', [
            'date' => '2025-12-25', 'name' => 'Navidad', 'is_recurring' => true,
        ])->assertCreated();

        $this->postJson('/api/admin/calendar/blackouts', [
            'date' => '2025-09-10', 'time_slot_id' => $slotId,
        ])->assertCreated();

        $this->assertDatabaseHas('calendar_time_slots', ['id' => $slotId]);
        $this->assertDatabaseHas('calendar_holidays', ['name' => 'Navidad', 'is_recurring' => true]);
        $this->assertDatabaseHas('calendar_blackouts', ['date' => '2025-09-10 00:00:00', 'time_slot_id' => $slotId]);
    }

    public function test_an_admin_can_set_a_production_rule(): void
    {
        $this->actingAsCalendarAdmin();

        $this->putJson('/api/admin/calendar/production-rules', [
            'product_id' => null, 'lead_time_hours' => 72,
        ])->assertOk()->assertJsonPath('data.lead_time_hours', 72);

        $this->assertDatabaseHas('calendar_production_rules', ['product_id' => null, 'lead_time_hours' => 72]);
    }

    public function test_calendar_admin_requires_permission(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value); // no "manage calendar"
        Sanctum::actingAs($courier);

        $this->putJson('/api/admin/calendar/production-rules', [
            'lead_time_hours' => 24,
        ])->assertForbidden();
    }

    public function test_calendar_admin_requires_auth(): void
    {
        $this->getJson('/api/admin/calendar/schedule')->assertUnauthorized();
    }
}
