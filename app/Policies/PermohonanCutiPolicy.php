<?php

namespace App\Policies;

use App\Models\PermohonanCuti;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermohonanCutiPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin and Manajer can view all leave requests
        // Other users can only view their own
        return $user->hasRole(['admin', 'manajer']) || $user->exists;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PermohonanCuti $permohonanCuti): bool
    {
        // Admin and Manajer can view any leave request
        if ($user->hasRole(['admin', 'manajer'])) {
            return true;
        }
        
        // Users can only view their own leave requests
        return $user->id === $permohonanCuti->pegawai_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only non-admin users can create leave requests
        // Admin can only view and approve, not create
        return !$user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PermohonanCuti $permohonanCuti): bool
    {
        // Admin and Manajer can update any leave request
        if ($user->hasRole(['admin', 'manajer'])) {
            return true;
        }
        
        // Users can only update their own pending leave requests
        return $user->id === $permohonanCuti->pegawai_id && 
               $permohonanCuti->status === 'Menunggu';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PermohonanCuti $permohonanCuti): bool
    {
        // Admin can delete any leave request
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Users can only delete their own pending leave requests
        return $user->id === $permohonanCuti->pegawai_id && 
               $permohonanCuti->status === 'Menunggu';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PermohonanCuti $permohonanCuti): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PermohonanCuti $permohonanCuti): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can approve leave requests.
     */
    public function approve(User $user, PermohonanCuti $permohonanCuti): bool
    {
        // Only Admin and Manajer can approve leaves
        if (!$user->hasRole(['admin', 'manajer'])) {
            return false;
        }
        
        // Can't approve own leave request
        if ($user->id === $permohonanCuti->pegawai_id) {
            return false;
        }
        
        // Can only approve pending requests
        return $permohonanCuti->status === 'Menunggu';
    }

    /**
     * Determine whether the user can reject leave requests.
     */
    public function reject(User $user, PermohonanCuti $permohonanCuti): bool
    {
        return $this->approve($user, $permohonanCuti);
    }

    /**
     * Determine whether the user can add comments to leave requests.
     */
    public function addComment(User $user, PermohonanCuti $permohonanCuti): bool
    {
        // Admin and Manajer can add comments
        if ($user->hasRole(['admin', 'manajer'])) {
            return true;
        }
        
        // Users can add comments to their own requests
        return $user->id === $permohonanCuti->pegawai_id;
    }
}