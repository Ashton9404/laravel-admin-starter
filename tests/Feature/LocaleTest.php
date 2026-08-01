<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_answers_in_english_by_default(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'x']);

        $this->assertSame(
            'These credentials do not match our records.',
            $response->json('errors.email.0')
        );
    }

    public function test_the_api_honours_accept_language(): void
    {
        $response = $this->withHeader('Accept-Language', 'zh-TW')
            ->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'x']);

        $this->assertSame('帳號或密碼不正確。', $response->json('errors.email.0'));
    }

    public function test_an_unsupported_language_falls_back_instead_of_erroring(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
            ->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'x']);

        $this->assertSame(
            'These credentials do not match our records.',
            $response->json('errors.email.0')
        );
    }

    public function test_a_saved_preference_beats_the_browser_header(): void
    {
        $user = User::factory()->create(['locale' => 'zh-TW']);

        // The header says English; the account says Traditional Chinese. The
        // deliberate choice has to win over the browser's guess.
        $response = $this->actingAs($user)
            ->withHeader('Accept-Language', 'en-US')
            ->patchJson("/api/users/{$user->id}", ['email' => 'not-an-email']);

        $response->assertUnprocessable();
        $this->assertStringContainsString('Email', $response->json('errors.email.0'));
        $this->assertStringNotContainsString('must be a valid', $response->json('errors.email.0'));
    }

    public function test_a_user_can_save_their_locale(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)
            ->putJson('/api/user/locale', ['locale' => 'zh-TW'])
            ->assertOk()
            ->assertJsonPath('locale', 'zh-TW');

        $this->assertSame('zh-TW', $user->fresh()->locale);
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/user/locale', ['locale' => 'klingon'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('locale');
    }

    public function test_guests_cannot_save_a_locale(): void
    {
        $this->putJson('/api/user/locale', ['locale' => 'zh-TW'])->assertUnauthorized();
    }

    public function test_the_locale_is_exposed_on_the_user_resource(): void
    {
        $user = User::factory()->create(['locale' => 'zh-TW']);

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.locale', 'zh-TW');
    }

    public function test_accept_language_parsing_prefers_an_exact_tag(): void
    {
        $this->assertSame('zh-TW', Locales::fromAcceptLanguage('zh-TW,zh;q=0.9,en;q=0.8'));
        $this->assertSame('en', Locales::fromAcceptLanguage('en-US,en;q=0.9'));

        // zh-CN is Simplified Chinese and is not supported; guessing zh-TW from
        // it would show the wrong script rather than a safe fallback.
        $this->assertNull(Locales::fromAcceptLanguage('zh-CN'));
        $this->assertNull(Locales::fromAcceptLanguage(null));
    }
}
