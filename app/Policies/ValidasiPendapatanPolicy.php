<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pendapatan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ValidasiPendapatanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['bendahara', 'admin', 'manajer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pendapatan $pendapatan): bool
    {
        // Bendahara can view all pendapatan records
        if ($user->hasRole(['bendahara', 'admin', 'manajer'])) {
            return true;
        }

        // Petugas can only view their own records
        if ($user->hasRole('petugas')) {
            return $pendapatan->input_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only petugas and admin can create pendapatan records
        return $user->hasRole(['petugas', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pendapatan $pendapatan): bool
    {
        // Admin can update any record
        if ($user->hasRole('admin')) {
            return true;
        }

        // Bendahara can update validation status and notes
        if ($user->hasRole('bendahara')) {
            return true;
        }

        // Petugas can only update their own pending records
        if ($user->hasRole('petugas')) {
            return $pendapatan->input_by === $user->id && 
                   $pendapatan->status_validasi === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pendapatan $pendapatan): bool
    {
        // Only admin can delete records
        if ($user->hasRole('admin')) {
            return true;
        }

        // Petugas can delete their own pending records
        if ($user->hasRole('petugas')) {
            return $pendapatan->input_by === $user->id && 
                   $pendapatan->status_validasi === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pendapatan $pendapatan): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Pendapatan $pendapatan): bool
    {
        // Only bendahara and admin can approve
        if (!$user->hasRole(['bendahara', 'admin'])) {
            return false;
        }

        // Can only approve pending records
        if ($pendapatan->status_validasi !== 'pending') {
            Log::info('ValidasiPendapatanPolicy: Cannot approve non-pending record', [
                'user_id' => $user->id,
                'pendapatan_id' => $pendapatan->id,
                'current_status' => $pendapatan->status_validasi,
            ]);
            return false;
        }

        // Check business rules
        if ($this->exceedsApprovalLimit($user, $pendapatan)) {
            Log::warning('ValidasiPendapatanPolicy: Approval exceeds limit', [
                'user_id' => $user->id,
                'pendapatan_id' => $pendapatan->id,
                'nominal' => $pendapatan->nominal,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Pendapatan $pendapatan): bool
    {
        // Only bendahara and admin can reject
        if (!$user->hasRole(['bendahara', 'admin'])) {
            return false;
        }

        // Can only reject pending records
        return $pendapatan->status_validasi === 'pending';
    }

    /**
     * Determine whether the user can request revision.
     */
    public function requestRevision(User $user, Pendapatan $pendapatan): bool
    {
        // Only bendahara and admin can request revision
        if (!$user->hasRole(['bendahara', 'admin'])) {
            return false;
        }

        // Can only request revision for pending records
        return $pendapatan->status_validasi === 'pending';
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function bulkAction(User $user): bool
    {
        return $user->hasRole(['bendahara', 'admin']);
    }

    /**
     * Determine whether the user can export data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['bendahara', 'admin', 'manajer']);
    }

    /**
     * Determine whether the user can auto-approve small amounts.
     */
    public function autoApprove(User $user): bool
    {
        // Only senior bendahara or admin can use auto-approve
        return $user->hasRole('admin') || 
               ($user->hasRole('bendahara') && $this->isSeniorBendahara($user));
    }

    /**
     * Check if the approval exceeds the user's limit.
     */
    private function exceedsApprovalLimit(User $user, Pendapatan $pendapatan): bool
    {
        // Define approval limits based on role
        $limits = [
            'bendahara' => 1000000,  // 1 million IDR
            'admin' => PHP_INT_MAX,   // No limit for admin
        ];

        $userRoles = $user->getRoleNames()->toArray();
        $maxLimit = 0;

        foreach ($userRoles as $role) {
            if (isset($limits[$role])) {
                $maxLimit = max($maxLimit, $limits[$role]);
            }
        }

        return $pendapatan->nominal > $maxLimit;
    }

    /**
     * Check if the user is a senior bendahara.
     */
    private function isSeniorBendahara(User $user): bool
    {
        // Check if user has senior bendahara permissions
        // This could be based on a permission, a user flag, or years of experience
        return $user->hasPermissionTo('auto-approve-pendapatan') ||
               $user->hasRole('senior-bendahara');
    }

    /**
     * Check if user can view financial reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasRole(['bendahara', 'admin', 'manajer']);
    }

    /**
     * Check if user can generate detailed reports.
     */
    public function generateDetailedReports(User $user): bool
    {
        return $user->hasRole(['bendahara', 'admin']);
    }
}