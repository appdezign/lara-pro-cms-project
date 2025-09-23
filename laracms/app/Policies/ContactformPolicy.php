<?php

namespace Lara\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Lara\App\Models\Contactform;
use Lara\Common\Models\User;

class ContactformPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_contactform');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contactform $contactform): bool
    {
        return $user->can('view_contactform');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_contactform');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contactform $contactform): bool
    {
        return $user->can('update_contactform');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contactform $contactform): bool
    {
        return $user->can('delete_contactform');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_contactform');
    }

}
