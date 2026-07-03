<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\CMS\Domain\Models\Page;
use App\Shared\Infrastructure\Jobs\RecordModelAuditJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModelAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsCmsAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_updating_an_auditable_model_dispatches_a_background_audit_job(): void
    {
        $admin = $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['title' => 'Antes']);

        Bus::fake([RecordModelAuditJob::class]);

        $this->putJson("/api/admin/cms/pages/{$page->id}", [
            'title' => 'Después',
            'type' => $page->type->value,
            'status' => $page->status->value,
        ])->assertOk();

        Bus::assertDispatched(RecordModelAuditJob::class, function (RecordModelAuditJob $job) use ($page, $admin): bool {
            return $job->event === 'updated'
                && $job->auditableId === $page->id
                && $job->brandId === $page->brand_id
                && $job->userId === $admin->id
                && ($job->oldValues['title'] ?? null) === 'Antes'
                && ($job->newValues['title'] ?? null) === 'Después';
        });
    }

    public function test_creating_an_auditable_model_dispatches_a_created_audit_job(): void
    {
        $this->actingAsCmsAdmin();

        Bus::fake([RecordModelAuditJob::class]);

        Page::factory()->create(['title' => 'Nueva página']);

        Bus::assertDispatched(RecordModelAuditJob::class, function (RecordModelAuditJob $job): bool {
            return $job->event === 'created'
                && ($job->newValues['title'] ?? null) === 'Nueva página'
                && $job->oldValues === null;
        });
    }

    public function test_deleting_an_auditable_model_records_the_last_known_state(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['title' => 'Condenada']);

        Bus::fake([RecordModelAuditJob::class]);
        $page->delete();

        Bus::assertDispatched(RecordModelAuditJob::class, function (RecordModelAuditJob $job) use ($page): bool {
            return $job->event === 'deleted'
                && $job->auditableId === $page->id
                && ($job->oldValues['title'] ?? null) === 'Condenada'
                && $job->newValues === null;
        });
    }

    public function test_the_audit_job_persists_the_entry_with_tenant_and_actor(): void
    {
        $admin = Admin::factory()->create();
        $page = Page::factory()->create();

        (new RecordModelAuditJob(
            event: 'updated',
            auditableType: $page->getMorphClass(),
            auditableId: $page->id,
            brandId: $page->brand_id,
            userId: $admin->id,
            oldValues: ['title' => 'A'],
            newValues: ['title' => 'B'],
            ipAddress: '127.0.0.1',
        ))->handle();

        $this->assertDatabaseHas('model_audit_logs', [
            'event' => 'updated',
            'auditable_id' => $page->id,
            'brand_id' => $page->brand_id,
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_timestamps_and_hidden_attributes_are_never_audited(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create();

        Bus::fake([RecordModelAuditJob::class]);
        $page->update(['title' => 'Cambio']);

        Bus::assertDispatched(RecordModelAuditJob::class, function (RecordModelAuditJob $job): bool {
            return $job->event === 'updated'
                && ! array_key_exists('updated_at', $job->newValues ?? [])
                && ! array_key_exists('created_at', $job->newValues ?? []);
        });
    }
}
