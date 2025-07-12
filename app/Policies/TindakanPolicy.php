<?php

namespace App\Policies;

use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TindakanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-procedures');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tindakan $tindakan): bool
    {
        return $user->can('view-procedures');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-procedures');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tindakan $tindakan): bool
    {
        // User can edit if they have permission AND (they created it OR they're involved in it)
        return $user->can('edit-procedures') && (
            $tindakan->input_by === $user->id ||
            $tindakan->dokter_id === $user->id ||
            $tindakan->paramedis_id === $user->id ||
            $tindakan->non_paramedis_id === $user->id ||
            $user->hasRole(['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tindakan $tindakan): bool
    {
        return $user->can('delete-procedures') && (
            $tindakan->input_by === $user->id ||
            $user->hasRole(['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can perform procedures.
     */
    public function perform(User $user): bool
    {
        return $user->can('perform-procedures');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tindakan $tindakan): bool
    {
        return $user->can('delete-procedures');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tindakan $tindakan): bool
    {
        return $user->hasRole('admin');
    }
}