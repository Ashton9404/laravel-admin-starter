<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $status, int $order = 0, array $names = []): Product
    {
        $product = Product::factory()->create([
            'slug' => $slug,
            'status' => $status,
            'sort_order' => $order,
            'published_at' => $status === Product::PUBLISHED ? now() : null,
        ]);

        foreach ($names ?: ['en' => ucfirst($slug)] as $locale => $name) {
            $product->translations()->create([
                'locale' => $locale,
                'name' => $name,
                'summary' => "{$name} summary",
                'body' => "<p>{$name} body</p>",
            ]);
        }

        return $product;
    }

    public function test_anyone_can_read_the_catalogue_without_signing_in(): void
    {
        $this->product('lamp', Product::PUBLISHED);

        $this->getJson('/api/public/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'lamp');
    }

    public function test_drafts_are_not_listed(): void
    {
        $this->product('published-one', Product::PUBLISHED);
        $this->product('secret-one', Product::DRAFT);

        $response = $this->getJson('/api/public/products')->assertOk();

        $this->assertSame(['published-one'], array_column($response->json('data'), 'slug'));
    }

    /**
     * 404 rather than 403. A 403 would confirm the slug exists, which is
     * precisely what an unpublished product should not reveal.
     */
    public function test_a_draft_is_indistinguishable_from_a_slug_that_never_existed(): void
    {
        $this->product('secret-one', Product::DRAFT);

        // Debug off, because the responses have to be compared as production
        // sends them. With it on, Laravel attaches a stack trace and the two
        // would differ by line number alone.
        config(['app.debug' => false]);

        $draft = $this->getJson('/api/public/products/secret-one')->assertNotFound();
        $nothing = $this->getJson('/api/public/products/never-existed')->assertNotFound();

        $this->assertSame($nothing->json(), $draft->json());
        $this->assertStringNotContainsString('secret-one', $draft->getContent());
    }

    public function test_the_order_is_the_one_set_in_the_admin_panel(): void
    {
        $this->product('third', Product::PUBLISHED, 2);
        $this->product('first', Product::PUBLISHED, 0);
        $this->product('second', Product::PUBLISHED, 1);

        $response = $this->getJson('/api/public/products')->assertOk();

        $this->assertSame(
            ['first', 'second', 'third'],
            array_column($response->json('data'), 'slug')
        );
    }

    /**
     * The listing would otherwise ship every product's full article to render a
     * grid of cards.
     */
    public function test_the_body_is_sent_on_the_detail_page_only(): void
    {
        $this->product('lamp', Product::PUBLISHED);

        $this->getJson('/api/public/products')
            ->assertOk()
            ->assertJsonMissingPath('data.0.body');

        $this->getJson('/api/public/products/lamp')
            ->assertOk()
            ->assertJsonPath('data.body', '<p>Lamp body</p>');
    }

    /**
     * An allow-list, so a column added later is invisible until someone decides
     * it should be public.
     */
    public function test_internal_fields_never_reach_the_public_payload(): void
    {
        $this->product('lamp', Product::PUBLISHED);

        foreach (['/api/public/products', '/api/public/products/lamp'] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();

            foreach (['status', 'sort_order', '"id"', 'cover_path'] as $leak) {
                $this->assertStringNotContainsString($leak, $body, "{$url} leaked {$leak}");
            }
        }
    }

    public function test_the_visitors_language_selects_the_translation(): void
    {
        $this->product('lamp', Product::PUBLISHED, 0, ['en' => 'Desk lamp', 'zh-TW' => '檯燈']);

        $this->getJson('/api/public/products/lamp', ['Accept-Language' => 'zh-TW'])
            ->assertOk()
            ->assertJsonPath('data.name', '檯燈');

        $this->getJson('/api/public/products/lamp', ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Desk lamp');
    }

    /**
     * A product written in one language still has to render for everyone else,
     * rather than showing an empty card.
     */
    public function test_a_missing_translation_falls_back_instead_of_blanking(): void
    {
        $this->product('lamp', Product::PUBLISHED, 0, ['en' => 'Desk lamp']);

        $this->getJson('/api/public/products/lamp', ['Accept-Language' => 'zh-TW'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Desk lamp');
    }

    /**
     * Reading the catalogue is not an event; logging it would bury the entries
     * that matter under one row per visitor.
     */
    public function test_browsing_writes_nothing_to_the_activity_log(): void
    {
        $this->product('lamp', Product::PUBLISHED);
        $before = Activity::count();

        $this->getJson('/api/public/products')->assertOk();
        $this->getJson('/api/public/products/lamp')->assertOk();

        $this->assertSame($before, Activity::count());
    }
}
