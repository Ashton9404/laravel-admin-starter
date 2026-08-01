<?php

namespace Tests\Feature\Activity;

use App\Models\Activity;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->withRole(Role::ADMIN)->create();
    }

    public function test_guests_cannot_read_the_log(): void
    {
        $this->getJson('/api/activity')->assertUnauthorized();
    }

    /**
     * The log is how you find out what a manager did with their content
     * permissions, so it is not theirs to read.
     */
    public function test_a_manager_cannot_read_the_log(): void
    {
        $this->actingAs(User::factory()->withRole(Role::MANAGER)->create())
            ->getJson('/api/activity')
            ->assertForbidden();
    }

    public function test_the_log_is_read_only_over_http(): void
    {
        $activity = Activity::factory()->create();
        $before = Activity::count();

        $this->actingAs($this->admin);

        // 405 or 404 either way: what matters is that no route exists.
        $this->postJson('/api/activity', ['event' => 'created'])->assertStatus(405);
        $this->patchJson("/api/activity/{$activity->id}", [])->assertStatus(404);
        $this->deleteJson("/api/activity/{$activity->id}")->assertStatus(404);

        $this->assertSame($before, Activity::count());
        $this->assertModelExists($activity);
    }

    public function test_signing_in_and_out_is_recorded(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $this->postJson('/api/logout')->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'event' => Activity::LOGIN,
            'causer_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => Activity::LOGOUT,
            'causer_id' => $user->id,
        ]);
    }

    /**
     * A failed attempt belongs to the account someone tried to reach, not to the
     * person who owns it — and the password they typed is very often a real one
     * for some other service, so it must not be written down anywhere.
     */
    public function test_a_failed_sign_in_records_the_email_but_never_the_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password-42',
        ])->assertStatus(422);

        $activity = Activity::where('event', Activity::LOGIN_FAILED)->sole();

        $this->assertNull($activity->causer_id);
        $this->assertSame('user', $activity->subject_type);
        $this->assertSame($user->id, $activity->subject_id);
        $this->assertSame($user->email, $activity->properties['email']);
        $this->assertStringNotContainsString('wrong-password-42', json_encode($activity->properties));
    }

    public function test_creating_a_product_is_recorded_against_the_person_who_did_it(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/products', [
                'slug' => 'desk-lamp',
                'status' => Product::DRAFT,
                'translations' => ['en' => ['name' => 'Desk lamp']],
            ])
            ->assertCreated();

        $activity = Activity::where('event', Activity::CREATED)
            ->where('subject_type', 'product')
            ->sole();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('desk-lamp', $activity->subject_label);
    }

    public function test_an_update_records_the_old_value_beside_the_new_one(): void
    {
        $product = Product::factory()->create(['slug' => 'old-slug', 'status' => Product::DRAFT]);
        $product->translations()->create(['locale' => 'en', 'name' => 'Lamp']);

        $this->actingAs($this->admin)
            ->patchJson("/api/products/{$product->id}", [
                'slug' => 'new-slug',
                'status' => Product::DRAFT,
                'translations' => ['en' => ['name' => 'Lamp']],
            ])
            ->assertOk();

        $activity = Activity::where('event', Activity::UPDATED)->sole();

        $this->assertSame('old-slug', $activity->properties['changed']['slug']['from']);
        $this->assertSame('new-slug', $activity->properties['changed']['slug']['to']);
    }

    /**
     * The body lives in product_translations, so nothing on the products row
     * changes. Without the child touching its parent the edit would vanish; with
     * it, a full edit could just as easily be logged twice.
     */
    public function test_editing_only_the_translated_body_is_recorded_exactly_once(): void
    {
        $product = Product::factory()->create(['slug' => 'kept', 'status' => Product::DRAFT]);
        $product->translations()->create(['locale' => 'en', 'name' => 'Lamp', 'body' => '<p>Old</p>']);

        $this->actingAs($this->admin)
            ->patchJson("/api/products/{$product->id}", [
                'slug' => 'kept',
                'status' => Product::DRAFT,
                'translations' => ['en' => ['name' => 'Lamp', 'body' => '<p>New</p>']],
            ])
            ->assertOk();

        $this->assertSame(1, Activity::where('event', Activity::UPDATED)->count());
    }

    public function test_a_full_edit_is_also_recorded_exactly_once(): void
    {
        $product = Product::factory()->create(['slug' => 'before', 'status' => Product::DRAFT]);
        $product->translations()->create(['locale' => 'en', 'name' => 'Lamp', 'body' => '<p>Old</p>']);

        $this->actingAs($this->admin)
            ->patchJson("/api/products/{$product->id}", [
                'slug' => 'after',
                'status' => Product::PUBLISHED,
                'translations' => ['en' => ['name' => 'Lamp', 'body' => '<p>New</p>']],
            ])
            ->assertOk();

        $entries = Activity::where('event', Activity::UPDATED)->get();

        $this->assertCount(1, $entries);
        $this->assertArrayHasKey('slug', $entries->first()->properties['changed']);
    }

    /**
     * The whole point of logging a deletion is being able to read it afterwards,
     * when the row it refers to no longer exists.
     */
    public function test_a_deleted_subject_still_reads_as_itself(): void
    {
        $product = Product::factory()->create(['slug' => 'discontinued']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertOk();

        $activity = Activity::where('event', Activity::DELETED)->sole();

        $this->assertNull($activity->subject);
        $this->assertSame('discontinued', $activity->subject_label);
    }

    public function test_a_deleted_causer_still_reads_as_themselves(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create(['name' => 'Ada Lovelace']);

        $this->actingAs($manager)
            ->postJson('/api/products', [
                'slug' => 'gadget',
                'status' => Product::DRAFT,
                'translations' => ['en' => ['name' => 'Gadget']],
            ])
            ->assertCreated();

        $manager->delete();

        $activity = Activity::where('subject_type', 'product')->where('event', Activity::CREATED)->sole();

        $this->assertNull($activity->causer_id);
        $this->assertSame('Ada Lovelace', $activity->causer_name);
    }

    /**
     * That the password changed is worth recording. What it changed to is not:
     * a hash in a table administrators read is offline cracking material, and it
     * would outlive every later rotation of the real column.
     */
    public function test_a_password_change_is_recorded_without_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'brand-new-secret1',
                'password_confirmation' => 'brand-new-secret1',
            ])
            ->assertOk();

        $activity = Activity::where('event', Activity::UPDATED)->sole();
        $json = json_encode($activity->properties);

        $this->assertArrayHasKey('password', $activity->properties['changed']);
        $this->assertStringNotContainsString('brand-new-secret1', $json);
        $this->assertStringNotContainsString($user->fresh()->password, $json);
        $this->assertStringNotContainsString('$2y$', $json);
    }

    /**
     * The SPA writes the browser's language onto the account the first time
     * someone signs in. Recording that would put an "edited themselves" entry
     * against nearly every sign-in, and a log that mostly reports itself is a
     * log nobody reads.
     */
    public function test_changes_nobody_decided_to_make_are_not_recorded(): void
    {
        $this->actingAs($this->admin)
            ->putJson('/api/user/locale', ['locale' => 'zh-TW'])
            ->assertOk();

        $this->assertSame('zh-TW', $this->admin->fresh()->locale);
        $this->assertSame(0, Activity::where('event', Activity::UPDATED)->count());
    }

    /**
     * The drag goes through the query builder and fires no model events, so it
     * has to be logged by hand — once for the action, not once per row.
     */
    public function test_reordering_is_recorded_as_a_single_entry(): void
    {
        $products = Product::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->postJson('/api/products/reorder', ['ids' => $products->pluck('id')->all()])
            ->assertOk();

        $activity = Activity::where('event', Activity::REORDERED)->sole();

        $this->assertSame(3, $activity->properties['count']);
        $this->assertNull($activity->subject_type);
    }

    public function test_the_listing_is_newest_first_and_filterable(): void
    {
        Activity::factory()->create(['event' => Activity::LOGIN, 'created_at' => now()->subHour()]);
        Activity::factory()->create(['event' => Activity::LOGIN_FAILED, 'created_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson('/api/activity')->assertOk();

        $this->assertSame(Activity::LOGIN_FAILED, $response->json('data.0.event'));

        $this->actingAs($this->admin)
            ->getJson('/api/activity?event='.Activity::LOGIN)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', Activity::LOGIN);
    }

    /**
     * Rejected rather than silently returning nothing, which would look like
     * "there is no such activity" instead of "you asked the wrong question".
     */
    public function test_unknown_filter_values_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/activity?event=exploded')
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->getJson('/api/activity?subject_type=App\Models\Product')
            ->assertStatus(422);
    }

    /**
     * Class names in a polymorphic column make the namespace part of the
     * schema, and history already written cannot survive a rename.
     */
    public function test_subjects_are_stored_as_short_aliases(): void
    {
        $product = Product::factory()->create();

        // The admin created in setUp contributes the other one.
        $types = Activity::whereNotNull('subject_type')->pluck('subject_type')->unique()->sort()->values();

        $this->assertSame(['product', 'user'], $types->all());
        $this->assertTrue($product->is(Activity::latest('id')->first()->subject));
    }
}
