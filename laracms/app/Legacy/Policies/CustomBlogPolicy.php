<?php

namespace Lara\App\Legacy\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Lara\App\Legacy\Models\CustomBlog;
use Lara\Common\Models\User;

/**
 * Policy of the legacy (non-Livewire) custom blog example.
 *
 * The permission names follow the Role screen: `{action}_customblog`.
 */
class CustomBlogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_customblog');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomBlog $customBlog): bool
    {
        return $user->can('view_customblog');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_customblog');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomBlog $customBlog): bool
    {
        return $user->can('update_customblog');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomBlog $customBlog): bool
    {
        return $user->can('delete_customblog');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_customblog');
    }
}
