<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Notifications\Domain\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsNotificationsAdmin(): void
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage notifications"
        Sanctum::actingAs($admin);
    }

    public function test_an_admin_can_configure_a_template(): void
    {
        $this->actingAsNotificationsAdmin();

        $this->postJson('/api/admin/notifications/templates', [
            'event' => 'order.placed',
            'subject' => 'Gracias {{ customer_name }}',
            'body' => '<p>Pedido {{ order_number }}</p>',
        ])
            ->assertCreated()
            ->assertJsonPath('data.event', 'order.placed')
            ->assertJsonPath('data.event_label', 'Compra realizada')
            ->assertJsonStructure(['data' => ['variables']]);

        $this->assertDatabaseHas('notification_templates', ['event' => 'order.placed']);
    }

    public function test_template_event_must_be_unique(): void
    {
        $this->actingAsNotificationsAdmin();
        \App\Modules\Notifications\Domain\Models\NotificationTemplate::factory()
            ->event(\App\Modules\Notifications\Domain\Enums\NotificationEvent::OrderPlaced)->create();

        $this->postJson('/api/admin/notifications/templates', [
            'event' => 'order.placed', 'subject' => 'x', 'body' => 'y',
        ])->assertStatus(422)->assertJsonValidationErrors('event');
    }

    public function test_an_admin_can_manage_reminder_rules(): void
    {
        $this->actingAsNotificationsAdmin();

        $id = $this->postJson('/api/admin/notifications/reminders', ['offset_hours' => 12])
            ->assertCreated()->json('data.id');

        $this->deleteJson("/api/admin/notifications/reminders/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('reminder_rules', ['id' => $id]);
    }

    public function test_admin_can_view_the_notification_log(): void
    {
        $this->actingAsNotificationsAdmin();
        NotificationLog::create([
            'event' => 'order.placed', 'recipient' => 'a@b.test',
            'subject' => 'Hi', 'body' => 'x', 'status' => 'sent',
        ]);

        $this->getJson('/api/admin/notifications/logs')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_notifications_admin_requires_permission(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->getJson('/api/admin/notifications/templates')->assertForbidden();
    }

    public function test_guests_cannot_access_notifications_admin(): void
    {
        $this->getJson('/api/admin/notifications/templates')->assertUnauthorized();
    }
}
