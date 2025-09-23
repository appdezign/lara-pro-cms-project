<?php

namespace Lara\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Lara\App\Models\Classicform;
use Lara\Common\Models\User;

class ClassicformPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_classicform');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Classicform $classicform): bool
    {
        return $user->can('view_classicform');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_classicform');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Classicform $classicform): bool
    {
        return $user->can('update_classicform');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Classicform $classicform): bool
    {
        return $user->can('delete_classicform');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_classicform');
    }
}
