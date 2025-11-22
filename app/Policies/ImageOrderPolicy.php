<?php

namespace App\Policies;

use App\Models\ImageOrder;
use App\Models\User;

class ImageOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImageOrder $imageOrder): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can create models.
     * Public form - everyone can create
     */
    public function create(?User $user): bool
    {
        return true; // Accessible publiquement
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImageOrder $imageOrder): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImageOrder $imageOrder): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImageOrder $imageOrder): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImageOrder $imageOrder): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }

    /**
     * Determine whether the user can export orders.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('manage-orders');
    }
}
