<?php

namespace App\Policies;

use App\Models\Agenda;
use App\Models\User;

class AgendaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-agendas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Agenda $agenda): bool
    {
        return $user->hasPermissionTo('manage-agendas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-agendas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Agenda $agenda): bool
    {
        // User can update if they are the uploader OR if they are Super-admin
        return $agenda->uploaded_by === $user->id || $user->hasRole('Super-admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Agenda $agenda): bool
    {
        // User can delete if they are the uploader OR if they are Super-admin
        return $agenda->uploaded_by === $user->id || $user->hasRole('Super-admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Agenda $agenda): bool
    {
        return $user->hasRole('Super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Agenda $agenda): bool
    {
        return $user->hasRole('Super-admin');
    }
}
