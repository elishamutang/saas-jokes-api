<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAllPermissions(['browse all categories', 'search any category']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('read any category');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create a category');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('edit any category');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('delete any category');
    }

    /**
     * Determine whether the user can access soft-deleted categories.
     *
     * @param User $user
     * @return bool
     */
    public function checkTrash(User $user): bool
    {
        return $user->hasPermissionTo('browse soft-deleted categories');
    }

    /**
     * Determine whether the user can restore all soft-deleted categories.
     *
     * @param User $user
     * @return bool
     */
    public function restore(User $user): bool
    {
        return $user->hasPermissionTo('restore soft-deleted categories');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function remove(User $user): bool
    {
        return $user->hasPermissionTo('remove soft-deleted categories');
    }
}
