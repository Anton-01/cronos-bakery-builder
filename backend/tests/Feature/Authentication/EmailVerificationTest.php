<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_verify_their_email_via_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect();

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_verification_fails_with_a_tampered_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1('wrong@email.test'),
        ]);

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_unsigned_verification_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/auth/email/verify/{$user->id}/" . sha1($user->email))
            ->assertStatus(403);
    }
}
