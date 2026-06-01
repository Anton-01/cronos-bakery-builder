<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_receive_a_token(): void
    {
        $user = User::factory()->create(['email' => 'jane@cronos.test']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@cronos.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'roles'], 'token']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'jane@cronos.test']);

        $this->postJson('/api/auth/login', [
            'email' => 'jane@cronos.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }
}
