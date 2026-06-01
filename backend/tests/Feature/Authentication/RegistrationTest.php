<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Authentication\Infrastructure\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_register_with_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Markus',
            'last_name' => 'Piedra',
            'email' => 'markus@cronos.test',
            'phone' => '+50688887777',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'Sup3r-Secret!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email', 'phone'], 'token'])
            ->assertJsonPath('user.email', 'markus@cronos.test');

        $this->assertDatabaseHas('users', [
            'email' => 'markus@cronos.test',
            'first_name' => 'Markus',
            'last_name' => 'Piedra',
            'role' => 'customer',
        ]);

        $user = User::whereEmail('markus@cronos.test')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@cronos.test']);

        $this->postJson('/api/auth/register', [
            'first_name' => 'Markus',
            'last_name' => 'Piedra',
            'email' => 'taken@cronos.test',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'Sup3r-Secret!',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_registration_enforces_password_confirmation(): void
    {
        $this->postJson('/api/auth/register', [
            'first_name' => 'Markus',
            'last_name' => 'Piedra',
            'email' => 'markus@cronos.test',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'does-not-match',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
