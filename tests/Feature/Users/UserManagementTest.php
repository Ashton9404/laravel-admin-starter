<?php

namespace Tests\Feature\Users;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->manager = User::factory()->withRole(Role::MANAGER)->create([
            'name' => 'Manager User',
            'email' => 'manager@example.test',
        ]);
    }

    public function test_guests_cannot_list_users(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_a_user_without_users_view_cannot_list_users(): void
    {
        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_the_index_is_paginated(): void
    {
        User::factory()->count(20)->create();

        $this->actingAs($this->manager)
            ->getJson('/api/users?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_users_can_be_searched_by_name_or_email(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.test']);

        $byName = $this->actingAs($this->manager)->getJson('/api/users?search=Lovelace')->assertOk();
        $this->assertCount(1, $byName->json('data'));
        $this->assertSame('Ada Lovelace', $byName->json('data.0.name'));

        $byEmail = $this->actingAs($this->manager)->getJson('/api/users?search=grace@')->assertOk();
        $this->assertCount(1, $byEmail->json('data'));
    }

    public function test_users_can_be_filtered_by_role_and_verification(): void
    {
        User::factory()->count(2)->unverified()->create();

        $byRole = $this->actingAs($this->manager)->getJson('/api/users?role='.Role::MANAGER)->assertOk();
        $this->assertCount(1, $byRole->json('data'));

        $unverified = $this->actingAs($this->manager)->getJson('/api/users?verified=0')->assertOk();
        $this->assertCount(2, $unverified->json('data'));
    }

    public function test_sorting_is_restricted_to_a_whitelist(): void
    {
        // An arbitrary column would be interpolated straight into the SQL.
        $this->actingAs($this->manager)
            ->getJson('/api/users?sort=password')
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('sort');

        $this->actingAs($this->manager)
            ->getJson('/api/users?sort=name&direction=asc')
            ->assertOk();
    }

    public function test_a_manager_can_create_a_user(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)
            ->postJson('/api/users', [
                'name' => 'New Person',
                'email' => 'new@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => [Role::USER],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'new@example.test')
            ->assertJsonPath('data.roles', [Role::USER]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.test']);
    }

    public function test_a_manager_cannot_grant_the_admin_role(): void
    {
        // Managers hold users.create; without this guard they could mint
        // themselves an administrator and bypass every gate.
        $this->actingAs($this->manager)
            ->postJson('/api/users', [
                'name' => 'Sneaky',
                'email' => 'sneaky@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => [Role::ADMIN],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('roles');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.test']);
    }

    public function test_a_manager_cannot_promote_themselves_to_admin(): void
    {
        $this->actingAs($this->manager)
            ->patchJson("/api/users/{$this->manager->id}", ['roles' => [Role::ADMIN]])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('roles');

        $this->assertFalse($this->manager->fresh()->hasRole(Role::ADMIN));
    }

    public function test_an_admin_can_grant_the_admin_role(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/users/{$target->id}", ['roles' => [Role::ADMIN]])
            ->assertOk()
            ->assertJsonPath('data.roles', [Role::ADMIN]);
    }

    public function test_changing_the_email_clears_the_verification(): void
    {
        $target = User::factory()->create();
        $this->assertNotNull($target->email_verified_at);

        $this->actingAs($this->manager)
            ->patchJson("/api/users/{$target->id}", ['email' => 'moved@example.test'])
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);

        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_the_password_is_hashed_on_update(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->manager)
            ->patchJson("/api/users/{$target->id}", [
                'password' => 'brand-new123',
                'password_confirmation' => 'brand-new123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new123', $target->fresh()->password));
    }

    public function test_a_manager_cannot_delete_users(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->manager)
            ->deleteJson("/api/users/{$target->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_an_admin_can_delete_another_user_but_not_themselves(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/users/{$target->id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_plain_user_can_read_and_update_only_their_own_record(): void
    {
        $user = User::factory()->withRole(Role::USER)->create();
        $other = User::factory()->create();

        $this->actingAs($user)->getJson("/api/users/{$user->id}")->assertOk();
        $this->actingAs($user)->getJson("/api/users/{$other->id}")->assertForbidden();

        $this->actingAs($user)
            ->patchJson("/api/users/{$user->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $this->actingAs($user)
            ->patchJson("/api/users/{$other->id}", ['name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_authorization_runs_before_validation(): void
    {
        $user = User::factory()->withRole(Role::USER)->create();
        $other = User::factory()->create();

        // Invalid payload AND no permission: the 403 must win, or the response
        // would leak the shape of a record the caller may not touch.
        $this->actingAs($user)
            ->patchJson("/api/users/{$other->id}", ['email' => 'not-an-email'])
            ->assertForbidden();
    }

    public function test_the_listing_never_exposes_password_hashes(): void
    {
        $response = $this->actingAs($this->manager)->getJson('/api/users')->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('data.0'));
        $this->assertArrayHasKey('permissions', $response->json('data.0'));
    }

    public function test_permission_constants_cover_the_seeded_set(): void
    {
        $this->assertTrue($this->manager->hasPermission(Permission::USERS_VIEW));
        $this->assertFalse($this->manager->hasPermission(Permission::USERS_DELETE));
    }
}
