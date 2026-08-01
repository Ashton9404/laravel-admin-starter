<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_manager_inherits_the_permissions_of_its_role(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->assertTrue($manager->hasRole(Role::MANAGER));
        $this->assertTrue($manager->hasPermission(Permission::USERS_VIEW));
        $this->assertTrue($manager->hasPermission(Permission::USERS_CREATE));
        $this->assertTrue($manager->hasPermission(Permission::USERS_UPDATE));

        // Deleting users is deliberately withheld from managers.
        $this->assertFalse($manager->hasPermission(Permission::USERS_DELETE));
    }

    public function test_a_plain_user_has_no_permissions(): void
    {
        $user = User::factory()->withRole(Role::USER)->create();

        $this->assertTrue($user->hasRole(Role::USER));
        $this->assertFalse($user->hasPermission(Permission::USERS_VIEW));
        $this->assertCount(0, $user->permissionNames());
    }

    public function test_an_admin_passes_every_gate_without_explicit_permissions(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        // The admin role has no permission rows at all; Gate::before covers it.
        $this->assertCount(0, $admin->permissionNames());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('delete', User::factory()->create()));
    }

    public function test_has_role_accepts_a_list(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->assertTrue($manager->hasRole([Role::ADMIN, Role::MANAGER]));
        $this->assertFalse($manager->hasRole([Role::ADMIN, Role::USER]));
    }

    public function test_permissions_are_resolved_once_per_instance(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create()->fresh();

        $queries = 0;
        \DB::listen(function () use (&$queries) {
            $queries++;
        });

        $manager->hasPermission(Permission::USERS_VIEW);
        $manager->hasPermission(Permission::USERS_CREATE);
        $manager->hasPermission(Permission::USERS_UPDATE);

        // Roles + permissions load once, then the memoised list answers the rest.
        $this->assertLessThanOrEqual(2, $queries);
    }

    public function test_deleting_a_role_detaches_it_from_users(): void
    {
        $user = User::factory()->withRole(Role::MANAGER)->create();

        Role::where('name', Role::MANAGER)->delete();

        $this->assertDatabaseMissing('role_user', ['user_id' => $user->id]);
    }
}
