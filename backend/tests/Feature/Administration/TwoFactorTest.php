<?php

declare(strict_types=1);

namespace Tests\Feature\Administration;

use App\Modules\Administration\Domain\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->google2fa = app(Google2FA::class);
    }

    public function test_an_admin_can_enrol_and_confirm_two_factor(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);

        $secret = $this->postJson('/api/admin/2fa/enable')
            ->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'otpauth_url']])
            ->json('data.secret');

        $code = $this->google2fa->getCurrentOtp($secret);

        $this->postJson('/api/admin/2fa/confirm', ['code' => $code])->assertOk();

        $this->assertTrue($admin->refresh()->hasTwoFactorEnabled());
    }

    public function test_confirming_with_a_wrong_code_fails(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/2fa/enable')->assertOk();

        $this->postJson('/api/admin/2fa/confirm', ['code' => '000000'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $this->assertFalse($admin->refresh()->hasTwoFactorEnabled());
    }

    public function test_login_requires_a_code_when_two_factor_is_enabled(): void
    {
        $secret = $this->google2fa->generateSecretKey();
        $admin = Admin::factory()->create([
            'email' => 'tfa@cronos.test',
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        // Correct password but no code → locked (HTTP 423).
        $this->postJson('/api/admin/login', [
            'email' => 'tfa@cronos.test', 'password' => 'password',
        ])->assertStatus(423)->assertJsonValidationErrors('code');

        // Wrong code → rejected.
        $this->postJson('/api/admin/login', [
            'email' => 'tfa@cronos.test', 'password' => 'password', 'code' => '000000',
        ])->assertStatus(422);

        // Correct code → success.
        $this->postJson('/api/admin/login', [
            'email' => 'tfa@cronos.test',
            'password' => 'password',
            'code' => $this->google2fa->getCurrentOtp($secret),
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_without_two_factor_still_works(): void
    {
        Admin::factory()->create(['email' => 'plain@cronos.test']);

        $this->postJson('/api/admin/login', [
            'email' => 'plain@cronos.test', 'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_an_admin_can_disable_two_factor(): void
    {
        $admin = Admin::factory()->create([
            'two_factor_secret' => $this->google2fa->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/2fa/disable')->assertOk();
        $this->assertFalse($admin->refresh()->hasTwoFactorEnabled());
    }
}
