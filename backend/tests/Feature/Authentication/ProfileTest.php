<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/auth/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '+50611112222',
        ])->assertOk()->assertJsonPath('data.first_name', 'Updated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'phone' => '+50611112222',
        ]);
    }

    public function test_a_customer_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-password')]);

        $this->actingAs($user)->putJson('/api/auth/profile/password', [
            'current_password' => 'current-password',
            'password' => 'A-New-Password1!',
            'password_confirmation' => 'A-New-Password1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('A-New-Password1!', $user->refresh()->password));
    }

    public function test_changing_password_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-password')]);

        $this->actingAs($user)->putJson('/api/auth/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'A-New-Password1!',
            'password_confirmation' => 'A-New-Password1!',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }
}
