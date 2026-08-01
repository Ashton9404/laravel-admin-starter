<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::USERS_VIEW);
    }

    public function view(User $user, User $target): bool
    {
        // Anyone may look at their own record, even without users.view.
        return $user->is($target) || $user->hasPermission(Permission::USERS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::USERS_CREATE);
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target) || $user->hasPermission(Permission::USERS_UPDATE);
    }

    public function delete(User $user, User $target): bool
    {
        // Deleting yourself would leave the session pointing at nothing, and an
        // admin doing it could lock the last administrator out of the system.
        if ($user->is($target)) {
            return false;
        }

        return $user->hasPermission(Permission::USERS_DELETE);
    }
}
