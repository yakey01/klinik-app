<?php

namespace App\Policies;

use App\Models\Pendapatan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PendapatanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_finance') || $user->hasPermissionTo('validate_transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasPermissionTo('manage_finance') || $user->hasPermissionTo('validate_transactions');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_finance');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pendapatan $pendapatan): bool
    {
        // Can edit if they have permission AND (they created it OR it's pending OR they're admin/manager)
        return $user->hasPermissionTo('manage_finance') && (
            $pendapatan->input_by === $user->id ||
            $pendapatan->status === 'pending' ||
            $user->hasRole(['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasPermissionTo('manage_finance') && (
            $pendapatan->input_by === $user->id ||
            $user->hasRole(['admin', 'manajer'])
        );
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasPermissionTo('validate_transactions') && $pendapatan->status === 'pending';
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasPermissionTo('validate_transactions') && $pendapatan->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasPermissionTo('manage_finance');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasRole('admin');
    }
}