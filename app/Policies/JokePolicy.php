<?php

namespace App\Policies;

use App\Models\Joke;
use App\Models\User;

class JokePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAllPermissions(['browse all jokes', 'search a joke']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Joke $joke): bool
    {
        return $user->hasPermissionTo('read any joke');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create a joke');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Joke $joke): bool
    {
        if ($user->hasPermissionTo('edit own joke') && $joke->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('edit any joke');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Joke $joke): bool
    {
        if ($user->hasPermissionTo('delete own joke') && $joke->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('delete any joke');
    }

    /**
     * Determine whether user can access soft-deleted jokes.
     *
     * @param User $user
     * @return bool
     */
    public function checkTrash(User $user): bool
    {
        return $user->hasPermissionTo('browse soft-deleted jokes');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->hasPermissionTo('restore soft-deleted jokes');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function remove(User $user): bool
    {
        return $user->hasPermissionTo('remove soft-deleted jokes');
    }
}
