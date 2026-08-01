<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * Memoised so a request that runs a dozen permission checks still costs
     * one query instead of a dozen.
     *
     * @var Collection<int, string>|null
     */
    private ?Collection $cachedPermissionNames = null;

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        return $this->roles->whereIn('name', (array) $roles)->isNotEmpty();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissionNames()->contains($permission);
    }

    /**
     * Every permission granted by any of the user's roles.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        // Sorted so the API payload is stable: without it the order falls out of
        // whatever the pivot join happens to return, which makes responses (and
        // any test asserting on them) quietly non-deterministic.
        return $this->cachedPermissionNames ??= $this->roles
            ->loadMissing('permissions')
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Drop the memoised permissions, e.g. after changing a user's roles.
     */
    public function forgetCachedPermissions(): static
    {
        $this->cachedPermissionNames = null;
        $this->unsetRelation('roles');

        return $this;
    }
}
