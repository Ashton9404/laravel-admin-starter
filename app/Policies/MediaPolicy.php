<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\Permission;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::MEDIA_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::MEDIA_UPLOAD);
    }

    /**
     * Uploaders can always clean up after themselves; removing someone else's
     * file needs the delete permission.
     */
    public function delete(User $user, Media $media): bool
    {
        return $media->uploaded_by === $user->getKey()
            || $user->hasPermission(Permission::MEDIA_DELETE);
    }
}
