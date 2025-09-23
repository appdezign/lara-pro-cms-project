<?php

namespace Lara\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Lara\App\Models\Doc;
use Lara\Common\Models\User;

class DocPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_doc');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Doc $doc): bool
    {
        return $user->can('view_doc');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_doc');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Doc $doc): bool
    {
        return $user->can('update_doc');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Doc $doc): bool
    {
        return $user->can('delete_doc');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_doc');
    }
}
