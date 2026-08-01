<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_manager_may_list_create_and_update_but_not_delete(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();
        $other = User::factory()->create();

        $this->assertTrue($manager->can('viewAny', User::class));
        $this->assertTrue($manager->can('create', User::class));
        $this->assertTrue($manager->can('update', $other));
        $this->assertFalse($manager->can('delete', $other));
    }

    public function test_a_plain_user_may_only_touch_their_own_record(): void
    {
        $user = User::factory()->withRole(Role::USER)->create();
        $other = User::factory()->create();

        $this->assertFalse($user->can('viewAny', User::class));
        $this->assertTrue($user->can('view', $user));
        $this->assertTrue($user->can('update', $user));
        $this->assertFalse($user->can('view', $other));
        $this->assertFalse($user->can('update', $other));
    }

    public function test_even_an_admin_may_not_delete_themselves(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        // Goes through the real Gate, not the policy object: the admin bypass in
        // Gate::before deliberately steps aside when the target is the actor, and
        // that wiring is the thing worth protecting against regressions.
        $this->assertFalse($admin->can('delete', $admin));
        $this->assertTrue($admin->can('delete', User::factory()->create()));
    }

    public function test_an_admin_may_still_act_on_their_own_record_where_allowed(): void
    {
        $admin = User::factory()->withRole(Role::ADMIN)->create();

        // Withholding the bypass hands the decision to UserPolicy, which permits
        // both of these for the owner — so the narrower bypass must not turn
        // admins into second-class citizens on their own account.
        $this->assertTrue($admin->can('view', $admin));
        $this->assertTrue($admin->can('update', $admin));
    }

    public function test_a_permitted_user_may_delete_someone_else(): void
    {
        $deleter = User::factory()->withRole(Role::MANAGER)->create();
        Role::where('name', Role::MANAGER)
            ->first()
            ->permissions()
            ->attach(Permission::where('name', Permission::USERS_DELETE)->first());
        $deleter->forgetCachedPermissions();

        $this->assertTrue($deleter->can('delete', User::factory()->create()));
        $this->assertFalse($deleter->can('delete', $deleter));
    }
}
