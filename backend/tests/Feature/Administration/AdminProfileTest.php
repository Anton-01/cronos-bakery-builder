<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsAdmin(): Admin
    {
        $admin = Admin::factory()->create(['password' => 'CurrentPass123']);
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_an_admin_can_update_their_personal_data(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/profile', ['name' => 'Nuevo Nombre', 'phone' => '+506 8888-8888'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre')
            ->assertJsonPath('data.phone', '+506 8888-8888');
    }

    public function test_an_admin_can_upload_and_delete_their_avatar(): void
    {
        Storage::fake('public');
        $admin = $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('me.png', 300, 300),
        ])->assertOk();

        $path = $admin->refresh()->avatar;
        $this->assertNotNull($path);
        // Random name under avatars/, never the client's original filename.
        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringNotContainsString('me.png', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($response->json('data.avatar'));

        $this->deleteJson('/api/admin/profile/avatar')->assertOk();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($admin->refresh()->avatar);
    }

    public function test_avatar_uploads_reject_non_image_files(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->postJson('/api/admin/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('payload.php', 10, 'text/php'),
        ])->assertUnprocessable()->assertJsonValidationErrors('avatar');
    }

    public function test_changing_the_password_requires_the_current_one_and_revokes_other_sessions(): void
    {
        $admin = Admin::factory()->create(['password' => 'CurrentPass123']);
        $admin->assignRole(AdminRole::Administrator->value);

        $current = $admin->createToken('admin', ['admin']);
        $other = $admin->createToken('admin', ['admin']);

        // Wrong current password is rejected.
        $this->withToken($current->plainTextToken)
            ->putJson('/api/admin/profile/password', [
                'current_password' => 'wrong',
                'password' => 'NewSecurePass99',
                'password_confirmation' => 'NewSecurePass99',
            ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withToken($current->plainTextToken)
            ->putJson('/api/admin/profile/password', [
                'current_password' => 'CurrentPass123',
                'password' => 'NewSecurePass99',
                'password_confirmation' => 'NewSecurePass99',
            ])->assertOk();

        $this->assertTrue(Hash::check('NewSecurePass99', $admin->refresh()->password));
        // The other device was signed out; the current token survives.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
    }

    public function test_an_admin_can_manage_notification_settings(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/profile/notifications', [
            'settings' => ['order_updates' => true, 'marketing' => false, 'unknown_key' => true],
        ])
            ->assertOk()
            ->assertJsonPath('data.notification_settings.order_updates', true)
            ->assertJsonPath('data.notification_settings.marketing', false)
            // Unknown channels are dropped, not persisted.
            ->assertJsonMissingPath('data.notification_settings.unknown_key');
    }

    public function test_login_records_ip_user_agent_and_device_on_the_token(): void
    {
        $admin = Admin::factory()->create(['password' => 'CurrentPass123']);
        $admin->assignRole(AdminRole::Administrator->value);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/126.0 Safari/537.36'])
            ->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'CurrentPass123'])
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'device_name' => 'Chrome · Windows',
        ]);
    }

    public function test_an_admin_can_list_and_revoke_their_sessions(): void
    {
        $admin = Admin::factory()->create(['password' => 'CurrentPass123']);
        $admin->assignRole(AdminRole::Administrator->value);

        $current = $admin->createToken('admin', ['admin']);
        $other = $admin->createToken('admin', ['admin']);
        $other->accessToken->forceFill(['ip_address' => '10.0.0.9', 'device_name' => 'Safari · macOS'])->save();

        $list = $this->withToken($current->plainTextToken)
            ->getJson('/api/admin/profile/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data');

        $currentRow = collect($list)->firstWhere('is_current', true);
        $this->assertNotNull($currentRow);
        $this->assertSame((int) $current->accessToken->id, $currentRow['id']);

        // The current session cannot be revoked through this endpoint.
        $this->withToken($current->plainTextToken)
            ->deleteJson("/api/admin/profile/sessions/{$current->accessToken->id}")
            ->assertUnprocessable();

        // A specific device can be signed out.
        $this->withToken($current->plainTextToken)
            ->deleteJson("/api/admin/profile/sessions/{$other->accessToken->id}")
            ->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);

        // "Revoke others" keeps only the current device.
        $admin->createToken('admin', ['admin']);
        $this->withToken($current->plainTextToken)
            ->postJson('/api/admin/profile/sessions/revoke-others')
            ->assertOk();
        $this->assertSame(1, $admin->tokens()->count());
    }

    public function test_sessions_cannot_be_revoked_across_accounts(): void
    {
        $victim = Admin::factory()->create();
        $victimToken = $victim->createToken('admin', ['admin']);

        $attacker = Admin::factory()->create();
        $attacker->assignRole(AdminRole::Administrator->value);
        $attackerToken = $attacker->createToken('admin', ['admin']);

        $this->withToken($attackerToken->plainTextToken)
            ->deleteJson("/api/admin/profile/sessions/{$victimToken->accessToken->id}")
            ->assertNotFound();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $victimToken->accessToken->id]);
    }
}
