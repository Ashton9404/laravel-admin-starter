<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Throwaway routes: the middleware is the unit under test here, not any
        // particular endpoint.
        Route::middleware(['auth:sanctum', 'permission:'.Permission::USERS_VIEW])
            ->get('/api/test/needs-users-view', fn () => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'permission:'.Permission::USERS_DELETE])
            ->get('/api/test/needs-users-delete', fn () => response()->json(['ok' => true]));

        Route::middleware([
            'auth:sanctum',
            'permission:'.Permission::USERS_DELETE.','.Permission::USERS_VIEW,
        ])->get('/api/test/needs-either', fn () => response()->json(['ok' => true]));
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->getJson('/api/test/needs-users-view')->assertUnauthorized();
    }

    public function test_a_user_with_the_permission_is_allowed_through(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->actingAs($manager)
            ->getJson('/api/test/needs-users-view')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->actingAs($manager)
            ->getJson('/api/test/needs-users-delete')
            ->assertForbidden();
    }

    public function test_a_role_less_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/test/needs-users-view')
            ->assertForbidden();
    }

    public function test_an_admin_is_allowed_through_anything(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        $this->actingAs($admin)->getJson('/api/test/needs-users-view')->assertOk();
        $this->actingAs($admin)->getJson('/api/test/needs-users-delete')->assertOk();
    }

    public function test_any_one_of_several_permissions_is_enough(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        // The manager lacks users.delete but has users.view.
        $this->actingAs($manager)->getJson('/api/test/needs-either')->assertOk();
    }
}
