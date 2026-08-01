<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        Permission::USERS_VIEW => 'View users',
        Permission::USERS_CREATE => 'Create users',
        Permission::USERS_UPDATE => 'Update users',
        Permission::USERS_DELETE => 'Delete users',
    ];

    /**
     * Admin is intentionally absent: the Gate::before hook in AppServiceProvider
     * grants it everything, so listing its permissions here would be a second
     * source of truth that could drift.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLES = [
        Role::ADMIN => [],
        Role::MANAGER => [
            Permission::USERS_VIEW,
            Permission::USERS_CREATE,
            Permission::USERS_UPDATE,
        ],
        Role::USER => [],
    ];

    /**
     * @var array<string, string>
     */
    private const ROLE_LABELS = [
        Role::ADMIN => 'Administrator',
        Role::MANAGER => 'Manager',
        Role::USER => 'User',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $label) {
            Permission::updateOrCreate(['name' => $name], ['label' => $label]);
        }

        foreach (self::ROLES as $name => $permissions) {
            $role = Role::updateOrCreate(['name' => $name], ['label' => self::ROLE_LABELS[$name]]);

            $role->permissions()->sync(
                Permission::whereIn('name', $permissions)->pluck('id')
            );
        }
    }
}
