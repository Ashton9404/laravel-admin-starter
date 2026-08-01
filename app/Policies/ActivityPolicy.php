<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

/**
 * Read-only by design. There is no create, update or delete ability here and no
 * route that could reach one: entries are written by the recorder and by nobody
 * else. A log an administrator can edit is a log that proves nothing.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ACTIVITY_VIEW);
    }
}
