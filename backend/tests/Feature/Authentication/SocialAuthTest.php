<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteUser(string $provider, array $attributes): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($attributes['id']);
        $socialUser->shouldReceive('getEmail')->andReturn($attributes['email']);
        $socialUser->shouldReceive('getName')->andReturn($attributes['name']);
        $socialUser->shouldReceive('getAvatar')->andReturn($attributes['avatar'] ?? null);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    public function test_callback_provisions_a_new_verified_customer(): void
    {
        $this->fakeSocialiteUser('google', [
            'id' => 'google-123',
            'email' => 'social@cronos.test',
            'name' => 'Jane Doe',
            'avatar' => 'https://example.test/a.png',
        ]);

        $this->getJson('/api/auth/social/google/callback')
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token'])
            ->assertJsonPath('user.email', 'social@cronos.test');

        $user = User::whereEmail('social@cronos.test')->firstOrFail();
        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    public function test_callback_links_provider_to_existing_user_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'social@cronos.test']);

        $this->fakeSocialiteUser('facebook', [
            'id' => 'fb-999',
            'email' => 'social@cronos.test',
            'name' => 'Jane Doe',
        ]);

        $this->getJson('/api/auth/social/facebook/callback')->assertOk();

        $this->assertSame(1, User::whereEmail('social@cronos.test')->count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existing->id,
            'provider' => 'facebook',
        ]);
    }

    public function test_unsupported_provider_is_rejected(): void
    {
        $this->getJson('/api/auth/social/twitter/callback')->assertStatus(422);
    }
}
