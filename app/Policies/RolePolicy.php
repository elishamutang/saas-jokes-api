<?php

namespace App\Policies;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['browse all roles', 'search any role']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('read any role');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create a role');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('edit any role');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function destroy(User $user, Role $role): bool
    {
        return false;
    }
}
