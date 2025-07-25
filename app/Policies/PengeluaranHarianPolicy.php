<?php

namespace App\Policies;

use App\Models\PengeluaranHarian;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PengeluaranHarianPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('petugas') ||
               $user->hasPermissionTo('view-finances') || 
               $user->hasPermissionTo('create-finances') ||
               $user->hasPermissionTo('validate_transactions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PengeluaranHarian $pengeluaranHarian): bool
    {
        return ($user->hasRole('petugas') ||
                $user->hasPermissionTo('view-finances') || 
                $user->hasPermissionTo('create-finances') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($pengeluaranHarian->user_id === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('petugas') ||
               $user->hasPermissionTo('create-finances');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PengeluaranHarian $pengeluaranHarian): bool
    {
        return ($user->hasRole('petugas') ||
                $user->hasPermissionTo('update_pengeluaran') || 
                $user->hasPermissionTo('input_transactions') ||
                $user->hasPermissionTo('validate_transactions')) &&
               ($pengeluaranHarian->user_id === $user->id || 
                $user->hasPermissionTo('validate_transactions'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PengeluaranHarian $pengeluaranHarian): bool
    {
        return ($user->hasRole('petugas') ||
                $user->hasPermissionTo('delete_pengeluaran') ||
                $user->hasPermissionTo('input_transactions')) &&
               ($pengeluaranHarian->user_id === $user->id || 
                $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PengeluaranHarian $pengeluaranHarian): bool
    {
        return $user->hasPermissionTo('restore_pengeluaran') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PengeluaranHarian $pengeluaranHarian): bool
    {
        return $user->hasPermissionTo('force_delete_pengeluaran') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete_any_pengeluaran') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('restore_any_pengeluaran') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently bulk delete models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->hasPermissionTo('force_delete_any_pengeluaran') ||
               $user->hasRole('admin');
    }
}