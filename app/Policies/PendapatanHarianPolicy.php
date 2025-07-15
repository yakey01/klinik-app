<?php

namespace App\Policies;

use App\Models\PendapatanHarian;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PendapatanHarianPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_pendapatan_harian') || 
               $user->hasPermissionTo('input_transactions') ||
               $user->hasPermissionTo('validate_transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PendapatanHarian $pendapatanHarian): bool
    {
        return ($user->hasPermissionTo('view_pendapatan_harian') || 
                $user->hasPermissionTo('input_transactions') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($pendapatanHarian->input_by === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_pendapatan_harian') ||
               $user->hasPermissionTo('input_transactions');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PendapatanHarian $pendapatanHarian): bool
    {
        return ($user->hasPermissionTo('edit_pendapatan_harian') || 
                $user->hasPermissionTo('input_transactions') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($pendapatanHarian->input_by === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PendapatanHarian $pendapatanHarian): bool
    {
        return ($user->hasPermissionTo('delete_pendapatan_harian') ||
                $user->hasPermissionTo('input_transactions')) &&
               ($pendapatanHarian->input_by === $user->id || 
                $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PendapatanHarian $pendapatanHarian): bool
    {
        return $user->hasPermissionTo('restore_pendapatan_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PendapatanHarian $pendapatanHarian): bool
    {
        return $user->hasPermissionTo('force_delete_pendapatan_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_any_pendapatan_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('restore_any_pendapatan_harian') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently bulk delete models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_any_pendapatan_harian') ||
               $user->hasRole('admin');
    }
}