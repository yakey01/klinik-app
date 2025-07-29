<?php

namespace App\Services;

use App\Models\Tindakan;
use App\Models\TindakanValidationHistory;
use App\Models\BendaharaValidationQueue;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TindakanValidationService
{
    /**
     * Submit tindakan for bendahara validation
     */
    public function submitForValidation(Tindakan $tindakan, ?string $notes = null): array
    {
        return DB::transaction(function () use ($tindakan, $notes) {
            // Update tindakan status
            $tindakan->update([
                'status_validasi' => 'pending',
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ]);

            // Log validation history
            TindakanValidationHistory::create([
                'tindakan_id' => $tindakan->id,
                'user_id' => Auth::id(),
                'action' => 'submitted',
                'old_status' => 'pending',
                'new_status' => 'pending',
                'comment' => $notes,
                'metadata' => [
                    'submitted_at' => now(),
                    'tarif' => $tindakan->tarif,
                    'jenis_tindakan' => $tindakan->jenisTindakan->nama ?? null,
                ]
            ]);

            // Add to bendahara validation queue
            $queueItem = BendaharaValidationQueue::create([
                'validation_type' => 'tindakan',
                'validatable_type' => Tindakan::class,
                'validatable_id' => $tindakan->id,
                'submitted_by' => Auth::id(),
                'priority' => $this->calculatePriority($tindakan),
                'status' => 'pending',
                'submitted_at' => now(),
                'amount' => $tindakan->tarif,
                'notes' => $notes,
                'validation_rules' => $this->getAutoValidationRules($tindakan),
            ]);

            // Check for auto-approval
            $autoApproval = $this->checkAutoApproval($tindakan);
            
            if ($autoApproval['eligible']) {
                return $this->autoApprove($tindakan, $queueItem, $autoApproval['reason']);
            }

            // Notify bendahara
            $this->notifyBendahara($tindakan, $queueItem);

            return [
                'success' => true,
                'message' => 'Tindakan berhasil diajukan untuk validasi',
                'auto_approved' => false,
                'queue_id' => $queueItem->id,
            ];
        });
    }

    /**
     * Approve tindakan (by bendahara/admin)
     */
    public function approve(Tindakan $tindakan, ?string $comment = null): array
    {
        return DB::transaction(function () use ($tindakan, $comment) {
            // Update tindakan
            $tindakan->update([
                'status_validasi' => 'disetujui',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'komentar_validasi' => $comment,
            ]);

            // Log history
            TindakanValidationHistory::create([
                'tindakan_id' => $tindakan->id,
                'user_id' => Auth::id(),
                'action' => 'approved',
                'old_status' => 'pending',
                'new_status' => 'disetujui',
                'comment' => $comment,
                'metadata' => [
                    'approved_at' => now(),
                    'approved_by_role' => Auth::user()->roles->pluck('name')->first(),
                ]
            ]);

            // Update queue status
            BendaharaValidationQueue::where('validatable_type', Tindakan::class)
                ->where('validatable_id', $tindakan->id)
                ->update([
                    'status' => 'completed',
                    'assigned_to' => Auth::id(),
                    'completed_at' => now(),
                ]);

            // Notify petugas
            $this->notifyPetugas($tindakan, 'approved', $comment);

            return [
                'success' => true,
                'message' => 'Tindakan berhasil disetujui',
            ];
        });
    }

    /**
     * Reject tindakan (by bendahara/admin)
     */
    public function reject(Tindakan $tindakan, string $reason): array
    {
        return DB::transaction(function () use ($tindakan, $reason) {
            // Update tindakan
            $tindakan->update([
                'status_validasi' => 'ditolak',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'komentar_validasi' => $reason,
            ]);

            // Log history
            TindakanValidationHistory::create([
                'tindakan_id' => $tindakan->id,
                'user_id' => Auth::id(),
                'action' => 'rejected',
                'old_status' => 'pending',
                'new_status' => 'ditolak',
                'comment' => $reason,
                'metadata' => [
                    'rejected_at' => now(),
                    'rejected_by_role' => Auth::user()->roles->pluck('name')->first(),
                ]
            ]);

            // Update queue status
            BendaharaValidationQueue::where('validatable_type', Tindakan::class)
                ->where('validatable_id', $tindakan->id)
                ->update([
                    'status' => 'rejected',
                    'assigned_to' => Auth::id(),
                    'completed_at' => now(),
                ]);

            // Notify petugas
            $this->notifyPetugas($tindakan, 'rejected', $reason);

            return [
                'success' => true,
                'message' => 'Tindakan berhasil ditolak',
            ];
        });
    }

    /**
     * Calculate validation priority based on tindakan properties
     */
    private function calculatePriority(Tindakan $tindakan): string
    {
        $amount = $tindakan->tarif;
        
        // High value transactions get higher priority
        if ($amount >= 1000000) return 'urgent';   // >= 1M
        if ($amount >= 500000) return 'high';      // >= 500K
        if ($amount >= 100000) return 'normal';    // >= 100K
        
        return 'low';
    }

    /**
     * Get auto-validation rules for tindakan
     */
    private function getAutoValidationRules(Tindakan $tindakan): array
    {
        return [
            'max_auto_approve_amount' => 50000,
            'requires_manual_review' => $tindakan->tarif > 50000,
            'user_auto_approve_limit' => 3, // per day
            'master_data_validation' => [
                'tarif_matches_master' => $tindakan->tarif == $tindakan->jenisTindakan->tarif,
                'jaspel_percentage_correct' => true,
            ]
        ];
    }

    /**
     * Check if tindakan is eligible for auto-approval
     */
    private function checkAutoApproval(Tindakan $tindakan): array
    {
        $rules = $this->getAutoValidationRules($tindakan);
        
        // Check amount limit
        if ($tindakan->tarif > $rules['max_auto_approve_amount']) {
            return ['eligible' => false, 'reason' => 'Amount exceeds auto-approval limit'];
        }

        // Check daily limit for user
        $todaySubmissions = Tindakan::where('input_by', $tindakan->input_by)
            ->whereDate('created_at', today())
            ->where('status_validasi', 'disetujui')
            ->count();

        if ($todaySubmissions >= $rules['user_auto_approve_limit']) {
            return ['eligible' => false, 'reason' => 'User daily auto-approval limit reached'];
        }

        // Check master data validation
        if (!$rules['master_data_validation']['tarif_matches_master']) {
            return ['eligible' => false, 'reason' => 'Tarif does not match master data'];
        }

        return ['eligible' => true, 'reason' => 'Meets auto-approval criteria'];
    }

    /**
     * Auto-approve tindakan
     */
    private function autoApprove(Tindakan $tindakan, BendaharaValidationQueue $queueItem, string $reason): array
    {
        // Update tindakan
        $tindakan->update([
            'status_validasi' => 'disetujui',
            'validated_by' => 1, // System user
            'validated_at' => now(),
            'komentar_validasi' => 'Auto-approved: ' . $reason,
        ]);

        // Log history
        TindakanValidationHistory::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => 1, // System user
            'action' => 'approved',
            'old_status' => 'pending',
            'new_status' => 'disetujui',
            'comment' => 'Auto-approved: ' . $reason,
            'metadata' => [
                'auto_approved' => true,
                'approval_reason' => $reason,
                'approved_at' => now(),
            ]
        ]);

        // Update queue
        $queueItem->update([
            'status' => 'completed',
            'assigned_to' => 1, // System user
            'completed_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Tindakan berhasil disetujui otomatis',
            'auto_approved' => true,
            'reason' => $reason,
        ];
    }

    /**
     * Notify bendahara about new validation request
     */
    private function notifyBendahara(Tindakan $tindakan, BendaharaValidationQueue $queueItem): void
    {
        $bendaharaUsers = User::role('bendahara')->get();
        
        foreach ($bendaharaUsers as $bendahara) {
            SystemNotification::create([
                'type' => 'validation_required',
                'title' => 'Validasi Tindakan Baru',
                'message' => "Tindakan {$tindakan->jenisTindakan->nama} sebesar {$tindakan->formatted_tarif} memerlukan validasi",
                'metadata' => [
                    'tindakan_id' => $tindakan->id,
                    'queue_id' => $queueItem->id,
                    'amount' => $tindakan->tarif,
                    'priority' => $queueItem->priority,
                ],
                'from_user_id' => $tindakan->input_by,
                'from_role' => 'petugas',
                'to_user_id' => $bendahara->id,
                'to_role' => 'bendahara',
                'priority' => $queueItem->priority,
                'related_type' => Tindakan::class,
                'related_id' => $tindakan->id,
            ]);
        }
    }

    /**
     * Notify petugas about validation result
     */
    private function notifyPetugas(Tindakan $tindakan, string $action, ?string $comment = null): void
    {
        $petugasUser = User::find($tindakan->input_by);
        
        if ($petugasUser) {
            $title = match($action) {
                'approved' => 'Tindakan Disetujui',
                'rejected' => 'Tindakan Ditolak',
                default => 'Update Validasi Tindakan'
            };

            $message = match($action) {
                'approved' => "Tindakan {$tindakan->jenisTindakan->nama} telah disetujui",
                'rejected' => "Tindakan {$tindakan->jenisTindakan->nama} ditolak: {$comment}",
                default => "Status validasi tindakan telah diperbarui"
            };

            SystemNotification::create([
                'type' => "validation_{$action}",
                'title' => $title,
                'message' => $message,
                'metadata' => [
                    'tindakan_id' => $tindakan->id,
                    'amount' => $tindakan->tarif,
                    'comment' => $comment,
                ],
                'from_user_id' => Auth::id(),
                'from_role' => 'bendahara',
                'to_user_id' => $petugasUser->id,
                'to_role' => 'petugas',
                'priority' => 'normal',
                'related_type' => Tindakan::class,
                'related_id' => $tindakan->id,
            ]);
        }
    }

    /**
     * Get validation statistics for dashboard
     */
    public function getValidationStats(): array
    {
        return [
            'pending_count' => BendaharaValidationQueue::pending()->count(),
            'in_review_count' => BendaharaValidationQueue::assigned()->count(),
            'completed_today' => BendaharaValidationQueue::where('completed_at', '>=', today())->count(),
            'total_amount_pending' => BendaharaValidationQueue::pending()->sum('amount'),
            'avg_processing_time' => BendaharaValidationQueue::whereNotNull('completed_at')
                ->whereDate('completed_at', '>=', now()->subDays(7))
                ->get()
                ->avg(function ($item) {
                    return $item->submitted_at->diffInMinutes($item->completed_at);
                }),
            'auto_approval_rate' => $this->getAutoApprovalRate(),
        ];
    }

    /**
     * Get auto-approval rate for the last 30 days
     */
    private function getAutoApprovalRate(): float
    {
        $total = TindakanValidationHistory::where('created_at', '>=', now()->subDays(30))
            ->where('action', 'approved')
            ->count();

        if ($total == 0) return 0;

        $autoApproved = TindakanValidationHistory::where('created_at', '>=', now()->subDays(30))
            ->where('action', 'approved')
            ->whereJsonContains('metadata->auto_approved', true)
            ->count();

        return ($autoApproved / $total) * 100;
    }
}