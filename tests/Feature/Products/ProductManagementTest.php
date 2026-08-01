<?php

namespace Tests\Feature\Products;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'steel-widget',
            'status' => Product::DRAFT,
            'translations' => [
                'en' => ['name' => 'Steel Widget', 'summary' => 'A widget.', 'body' => '<p>Made of steel.</p>'],
                'zh-TW' => ['name' => '鋼製小工具', 'summary' => '一個小工具。', 'body' => '<p>鋼製。</p>'],
            ],
        ], $overrides);
    }

    public function test_guests_cannot_list_products(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->getJson('/api/products')
            ->assertForbidden();
    }

    public function test_a_manager_can_create_a_product_in_two_languages(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/products', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'steel-widget')
            ->assertJsonPath('data.published', false)
            ->assertJsonPath('data.translations.en.name', 'Steel Widget')
            ->assertJsonPath('data.translations.zh-TW.name', '鋼製小工具');

        $this->assertDatabaseCount('product_translations', 2);
    }

    public function test_the_body_is_sanitised_before_it_is_stored(): void
    {
        $this->actingAs($this->manager)->postJson('/api/products', $this->validPayload([
            'translations' => [
                'en' => ['name' => 'X', 'body' => '<p>Hi</p><script>alert(1)</script><img src=x onerror=alert(1)>'],
            ],
        ]))->assertCreated();

        $body = Product::first()->translations->firstWhere('locale', 'en')->body;

        // The point of sanitising on write: nothing dangerous ever reaches the
        // database, so a template that forgets to escape cannot resurrect it.
        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringContainsString('Hi', $body);
    }

    public function test_the_slug_must_be_url_safe_and_unique(): void
    {
        Product::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->manager)
            ->postJson('/api/products', $this->validPayload(['slug' => 'Not A Slug!']))
            ->assertUnprocessable()->assertJsonValidationErrorFor('slug');

        $this->actingAs($this->manager)
            ->postJson('/api/products', $this->validPayload(['slug' => 'taken']))
            ->assertUnprocessable()->assertJsonValidationErrorFor('slug');
    }

    public function test_the_default_language_translation_is_required(): void
    {
        $this->actingAs($this->manager)->postJson('/api/products', $this->validPayload([
            'translations' => ['zh-TW' => ['name' => '只有中文']],
        ]))->assertUnprocessable()->assertJsonValidationErrorFor('translations');
    }

    public function test_an_unsupported_language_is_rejected(): void
    {
        $this->actingAs($this->manager)->postJson('/api/products', $this->validPayload([
            'translations' => [
                'en' => ['name' => 'X'],
                'klingon' => ['name' => 'Qapla'],
            ],
        ]))->assertUnprocessable()->assertJsonValidationErrorFor('translations');
    }

    public function test_new_products_go_to_the_end_of_the_catalogue(): void
    {
        Product::factory()->atPosition(7)->create();

        $this->actingAs($this->manager)->postJson('/api/products', $this->validPayload())->assertCreated();

        $this->assertSame(8, Product::where('slug', 'steel-widget')->value('sort_order'));
    }

    public function test_publishing_stamps_the_date_only_once(): void
    {
        $product = Product::factory()->withTranslations()->create();

        $this->actingAs($this->manager)
            ->patchJson("/api/products/{$product->id}", ['status' => Product::PUBLISHED])
            ->assertOk()->assertJsonPath('data.published', true);

        $first = $product->fresh()->published_at;
        $this->assertNotNull($first);

        // Back to draft and out again: the original publication date stands.
        $this->actingAs($this->manager)->patchJson("/api/products/{$product->id}", ['status' => Product::DRAFT]);
        $this->actingAs($this->manager)->patchJson("/api/products/{$product->id}", ['status' => Product::PUBLISHED]);

        $this->assertEquals($first, $product->fresh()->published_at);
    }

    public function test_search_and_status_filters_combine_correctly(): void
    {
        Product::factory()->published()->withTranslations(['en' => 'Blue Widget'])->create(['slug' => 'blue-widget']);
        Product::factory()->withTranslations(['en' => 'Blue Gadget'])->create(['slug' => 'blue-gadget']);
        Product::factory()->published()->withTranslations(['en' => 'Red Widget'])->create(['slug' => 'red-widget']);

        // Regression guard: an ungrouped OR would let the draft "Blue Gadget"
        // leak past the published filter.
        $data = $this->actingAs($this->manager)
            ->getJson('/api/products?search=Blue&status='.Product::PUBLISHED)
            ->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('blue-widget', $data[0]['slug']);
    }

    public function test_products_can_be_reordered_by_drag_and_drop(): void
    {
        $a = Product::factory()->atPosition(0)->create();
        $b = Product::factory()->atPosition(1)->create();
        $c = Product::factory()->atPosition(2)->create();

        $this->actingAs($this->manager)
            ->postJson('/api/products/reorder', ['ids' => [$c->id, $a->id, $b->id]])
            ->assertOk();

        // Position comes from the array index, so the result is always a clean
        // 0,1,2 with no gaps whatever the client sent.
        $this->assertSame(0, $c->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame(2, $b->fresh()->sort_order);
    }

    public function test_reordering_rejects_unknown_or_duplicate_ids(): void
    {
        $a = Product::factory()->create();

        $this->actingAs($this->manager)
            ->postJson('/api/products/reorder', ['ids' => [$a->id, 99999]])
            ->assertUnprocessable();

        $this->actingAs($this->manager)
            ->postJson('/api/products/reorder', ['ids' => [$a->id, $a->id]])
            ->assertUnprocessable();
    }

    public function test_a_plain_user_cannot_reorder(): void
    {
        $a = Product::factory()->create();

        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->postJson('/api/products/reorder', ['ids' => [$a->id]])
            ->assertForbidden();
    }

    public function test_a_manager_can_delete_a_product_and_its_translations(): void
    {
        $product = Product::factory()->withTranslations()->create();

        $this->actingAs($this->manager)->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_translations', ['product_id' => $product->id]);
    }

    public function test_a_cover_image_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $product = Product::factory()->withTranslations()->create();

        $first = $this->actingAs($this->manager)
            ->postJson("/api/products/{$product->id}/cover", ['cover' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk()->json('data.cover_url');

        $this->assertNotNull($first);
        $original = $product->fresh()->cover_path;
        Storage::disk('public')->assertExists($original);

        $this->actingAs($this->manager)
            ->postJson("/api/products/{$product->id}/cover", ['cover' => UploadedFile::fake()->image('b.png')])
            ->assertOk();

        // The replaced file is removed rather than left to accumulate.
        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertExists($product->fresh()->cover_path);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        $this->actingAs($this->manager)
            ->postJson("/api/products/{$product->id}/cover", [
                'cover' => UploadedFile::fake()->create('payload.php', 16, 'application/x-php'),
            ])
            ->assertUnprocessable()->assertJsonValidationErrorFor('cover');
    }

    public function test_managers_hold_every_product_permission(): void
    {
        $this->assertTrue($this->manager->hasPermission(Permission::PRODUCTS_DELETE));
        $this->assertTrue($this->manager->hasPermission(Permission::PRODUCTS_CREATE));
    }
}
