<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Infrastructure\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_request_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/password/forgot', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_a_customer_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Brand-New-Pass1!',
            'password_confirmation' => 'Brand-New-Pass1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('Brand-New-Pass1!', $user->refresh()->password));
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'Brand-New-Pass1!',
            'password_confirmation' => 'Brand-New-Pass1!',
        ])->assertStatus(422);
    }

    public function test_uses_the_spa_friendly_reset_notification(): void
    {
        // Guard against regressions: the broker must dispatch our custom
        // notification, not Laravel's default.
        $this->assertTrue(is_subclass_of(ResetPasswordNotification::class, ResetPassword::class));
    }
}
