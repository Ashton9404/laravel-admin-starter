<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_email_can_be_verified_through_a_signed_link(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user))
            ->assertRedirectContains('verified=1');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_works_without_an_authenticated_session(): void
    {
        $user = User::factory()->unverified()->create();

        // No actingAs(): the signed URL is the only credential, because the link
        // is typically opened from a mail client in a different browser.
        $this->assertGuest();

        $this->get($this->verificationUrl($user))->assertRedirectContains('verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_email_is_not_verified_with_an_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('wrong-email@example.com'),
        ]);

        $this->get($url)->assertRedirectContains('verified=0');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get("/api/verify-email/{$user->id}/".sha1($user->email))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_already_verified_user_is_sent_back_to_the_spa(): void
    {
        $user = User::factory()->create();

        $this->get($this->verificationUrl($user))
            ->assertRedirectContains('verified=already');
    }

    public function test_guests_cannot_request_another_verification_email(): void
    {
        $this->postJson('/api/email/verification-notification')->assertUnauthorized();
    }

    public function test_a_verified_user_is_told_there_is_nothing_to_do(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Your email address is already verified.');
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }
}
