<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('browse all users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('read any user');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['create a user', 'create client users only', 'create client and staff users only']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Check the role of the user being updated
        // Admin permissions
        if ($user->hasPermissionTo('edit admin, client or staff users only') && $model->hasAnyRole(['client', 'staff', 'admin'])) {
            return true;
        }

        // Staff permissions
        if ($user->hasPermissionTo('edit client users only') && $model->hasRole('client')) {
            return true;
        }

        // If updating own user data
        if ($user->hasPermissionTo('edit own user profile') && $model->id === $user->id) {
            return true;
        }

        // Client users cannot update other users
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->hasPermissionTo('delete client and staff users only') && $model->hasAnyRole(['client', 'staff'])) {
            return true;
        }

        if ($user->hasPermissionTo('delete client users only') && $model->hasRole('client')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can access soft-deleted users.
     */
    public function browseTrash(User $user): bool
    {
        return $user->hasAnyPermission(['browse soft-deleted users', 'browse soft-deleted client users']);
    }


    /**
     * Determine whether the user can recover the model.
     */
    public function recoverOne(User $user, User $model): bool
    {
        if ($user->hasPermissionTo('restore soft-deleted client users') && $model->hasRole('client')) {
            return true;
        }

        if ($user->hasPermissionTo('restore soft-deleted users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function removeOne(User $user, User $model): bool
    {
        if ($user->hasPermissionTo('remove soft-deleted client users') && $model->hasRole('client')) {
            return true;
        }

        if ($user->hasPermissionTo('remove soft-deleted users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can recover the model.
     */
    public function recoverAll(User $user): bool
    {
        return $user->hasAnyPermissions(['restore soft-deleted users', 'restore soft-deleted client users']);
    }

    /**
     * Determine whether the user can remove the model.
     */
    public function removeAll(User $user): bool
    {
        return $user->hasAnyPermissions(['remove soft-deleted users', 'remove soft-deleted client users']);
    }

}
