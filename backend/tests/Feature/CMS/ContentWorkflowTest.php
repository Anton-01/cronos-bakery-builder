<?php

declare(strict_types=1);

namespace Tests\Feature\CMS;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentWorkflowTest extends TestCase
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

    public function test_a_draft_page_can_be_submitted_for_review(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(); // draft by default

        $this->postJson("/api/admin/cms/pages/{$page->id}/submit-review", ['comment' => 'Lista para revisar'])
            ->assertCreated()
            ->assertJsonPath('data.from_status', 'draft')
            ->assertJsonPath('data.to_status', 'pending_review');

        $this->assertSame('pending_review', $page->refresh()->status->value);
        $this->assertDatabaseHas('content_versions', [
            'versionable_id' => $page->id,
            'version_number' => 1,
            'status_after' => 'pending_review',
        ]);
    }

    public function test_a_pending_page_can_be_approved_and_published(): void
    {
        $admin = $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['status' => 'pending_review']);

        $this->postJson("/api/admin/cms/pages/{$page->id}/approve")
            ->assertCreated()
            ->assertJsonPath('data.to_status', 'published')
            ->assertJsonPath('data.approved_by', $admin->id);

        $page->refresh();
        $this->assertSame('published', $page->status->value);
        $this->assertNotNull($page->published_at);
    }

    public function test_an_illegal_transition_is_rejected_with_a_422(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(); // draft

        // draft → published directly is not an allowed transition.
        $this->postJson("/api/admin/cms/pages/{$page->id}/approve")
            ->assertUnprocessable();

        $this->assertSame('draft', $page->refresh()->status->value);
    }

    public function test_a_rejected_page_returns_to_draft_with_the_reason_recorded(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['status' => 'pending_review']);

        $this->postJson("/api/admin/cms/pages/{$page->id}/reject", ['reason' => 'Faltan imágenes'])
            ->assertCreated()
            ->assertJsonPath('data.to_status', 'draft')
            ->assertJsonPath('data.comment', 'Faltan imágenes');

        $this->assertSame('draft', $page->refresh()->status->value);
    }

    public function test_a_publication_can_be_scheduled(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['status' => 'pending_review']);

        $this->postJson("/api/admin/cms/pages/{$page->id}/schedule", [
            'publish_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()->assertJsonPath('data.to_status', 'scheduled');

        $page->refresh();
        $this->assertSame('scheduled', $page->status->value);
        $this->assertNotNull($page->scheduled_at);
    }

    public function test_the_version_history_is_listed_most_recent_first(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create();

        $this->postJson("/api/admin/cms/pages/{$page->id}/submit-review");
        $this->postJson("/api/admin/cms/pages/{$page->id}/approve");

        $this->getJson("/api/admin/cms/pages/{$page->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version_number', 2)
            ->assertJsonPath('data.1.version_number', 1);

        $this->getJson("/api/admin/cms/pages/{$page->id}/workflows")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_page_can_be_rolled_back_to_a_previous_version(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create(['title' => 'Título original']);

        // Transition to capture version #1 (payload_before holds the original state).
        $this->postJson("/api/admin/cms/pages/{$page->id}/submit-review")->assertCreated();

        $page->update(['title' => 'Título corrupto']);
        $versionId = (int) $this->getJson("/api/admin/cms/pages/{$page->id}/versions")->json('data.0.id');

        $this->postJson("/api/admin/cms/pages/{$page->id}/rollback", ['version_id' => $versionId])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título original');

        // The rollback itself is versioned.
        $this->getJson("/api/admin/cms/pages/{$page->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_rolling_back_to_a_version_of_another_page_is_rejected(): void
    {
        $this->actingAsCmsAdmin();
        $pageA = Page::factory()->create();
        $pageB = Page::factory()->create();

        $this->postJson("/api/admin/cms/pages/{$pageA->id}/submit-review")->assertCreated();
        $versionId = (int) $this->getJson("/api/admin/cms/pages/{$pageA->id}/versions")->json('data.0.id');

        $this->postJson("/api/admin/cms/pages/{$pageB->id}/rollback", ['version_id' => $versionId])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('version_id');
    }
}
