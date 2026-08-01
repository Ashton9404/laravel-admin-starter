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
        Permission::PRODUCTS_VIEW => 'View products',
        Permission::PRODUCTS_CREATE => 'Create products',
        Permission::PRODUCTS_UPDATE => 'Update products',
        Permission::PRODUCTS_DELETE => 'Delete products',
        Permission::MEDIA_VIEW => 'View the media library',
        Permission::MEDIA_UPLOAD => 'Upload files',
        Permission::MEDIA_DELETE => 'Delete files',
        Permission::ACTIVITY_VIEW => 'View the activity log',
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
        // Managers own the content: full control over products, but deleting
        // user accounts stays with administrators.
        //
        // activity.view is withheld for a different reason than the rest. The
        // log exists partly to show what a manager did with those content
        // permissions, so handing them the log too would let the watched pick
        // their own watchers.
        Role::MANAGER => [
            Permission::USERS_VIEW,
            Permission::USERS_CREATE,
            Permission::USERS_UPDATE,
            Permission::PRODUCTS_VIEW,
            Permission::PRODUCTS_CREATE,
            Permission::PRODUCTS_UPDATE,
            Permission::PRODUCTS_DELETE,
            Permission::MEDIA_VIEW,
            Permission::MEDIA_UPLOAD,
            Permission::MEDIA_DELETE,
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
