<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_VIEW);
    }

    public function view(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_CREATE);
    }

    public function update(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_UPDATE);
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_DELETE);
    }

    /**
     * Reordering is an update to the catalogue as a whole rather than to any one
     * product, so it maps onto the update permission without a model argument.
     */
    public function reorder(User $user): bool
    {
        return $user->hasPermission(Permission::PRODUCTS_UPDATE);
    }
}
