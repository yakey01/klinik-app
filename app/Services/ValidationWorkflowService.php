<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;

class ValidationWorkflowService
{
    protected TelegramService $telegramService;
    protected NotificationService $notificationService;
    
    protected array $validationStates = [
        'pending' => 'Menunggu Validasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'revision' => 'Perlu Revisi',
        'cancelled' => 'Dibatalkan',
    ];

    protected array $validationRules = [
        'Tindakan' => [
            'required_fields' => ['jenis_tindakan_id', 'pasien_id', 'tanggal_tindakan', 'tarif'],
            'approval_levels' => ['supervisor', 'manager'],
            'auto_approve_threshold' => 100000, // Auto approve below 100k
            'requires_reason' => ['rejected', 'revision'],
        ],
        'PendapatanHarian' => [
            'required_fields' => ['pendapatan_id', 'nominal', 'tanggal_input'],
            'approval_levels' => ['supervisor'],
            'auto_approve_threshold' => 500000, // Auto approve below 500k
            'requires_reason' => ['rejected'],
        ],
        'PengeluaranHarian' => [
            'required_fields' => ['pengeluaran_id', 'nominal', 'tanggal_input'],
            'approval_levels' => ['supervisor', 'manager'],
            'auto_approve_threshold' => 200000, // Auto approve below 200k
            'requires_reason' => ['rejected'],
        ],
    ];

    public function __construct(TelegramService $telegramService, NotificationService $notificationService = null)
    {
        $this->telegramService = $telegramService;
        $this->notificationService = $notificationService ?? new NotificationService($telegramService);
    }

    /**
     * Submit record for validation
     */
    public function submitForValidation(Model $record, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $modelName = class_basename($record);
            $rules = $this->validationRules[$modelName] ?? [];

            // Validate required fields
            $this->validateRequiredFields($record, $rules['required_fields'] ?? []);

            // Check for auto-approval
            $autoApprove = $this->shouldAutoApprove($record, $rules);

            if ($autoApprove) {
                $result = $this->approve($record, [
                    'reason' => 'Auto-approved based on threshold',
                    'approved_by' => 'system',
                    'auto_approved' => true,
                ]);
            } else {
                // Update status to pending
                $record->update([
                    'status_validasi' => 'pending',
                    'submitted_at' => now(),
                    'submitted_by' => Auth::id(),
                ]);

                // Notify approvers
                $this->notifyApprovers($record, 'submitted');

                // Send real-time notification
                $this->sendRealTimeNotification($record, 'validation_submitted');

                $result = [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Record submitted for validation',
                    'auto_approved' => false,
                ];
            }

            // Log submission
            $this->logValidationAction($record, 'submitted', $result);

            DB::commit();
            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Validation submission failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve record
     */
    public function approve(Model $record, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $approvedBy = $options['approved_by'] ?? Auth::id();
            $reason = $options['reason'] ?? '';
            $autoApproved = $options['auto_approved'] ?? false;

            // Update record status
            $record->update([
                'status_validasi' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
                'approval_reason' => $reason,
                'auto_approved' => $autoApproved,
            ]);

            // Update related status if applicable
            if ($record->hasAttribute('status') && $record->status === 'pending') {
                $record->update(['status' => 'selesai']);
            }

            // Notify stakeholders
            $this->notifyApprovers($record, 'approved');
            $this->notifySubmitter($record, 'approved');

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_approved');

            // Log approval
            $this->logValidationAction($record, 'approved', [
                'approved_by' => $approvedBy,
                'reason' => $reason,
                'auto_approved' => $autoApproved,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'approved',
                'message' => 'Record approved successfully',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approval failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject record
     */
    public function reject(Model $record, string $reason, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $rejectedBy = $options['rejected_by'] ?? Auth::id();

            // Update record status
            $record->update([
                'status_validasi' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            // Update related status if applicable
            if ($record->hasAttribute('status') && $record->status !== 'batal') {
                $record->update(['status' => 'batal']);
            }

            // Notify stakeholders
            $this->notifySubmitter($record, 'rejected', $reason);

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_rejected');

            // Log rejection
            $this->logValidationAction($record, 'rejected', [
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'rejected',
                'message' => 'Record rejected',
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'reason' => $reason,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Rejection failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Request revision
     */
    public function requestRevision(Model $record, string $reason, array $options = []): array
    {
        try {
            DB::beginTransaction();

            $requestedBy = $options['requested_by'] ?? Auth::id();

            // Update record status
            $record->update([
                'status_validasi' => 'revision',
                'revision_requested_at' => now(),
                'revision_requested_by' => $requestedBy,
                'revision_reason' => $reason,
            ]);

            // Notify submitter
            $this->notifySubmitter($record, 'revision_requested', $reason);

            // Send real-time notification
            $this->sendRealTimeNotification($record, 'validation_revision');

            // Log revision request
            $this->logValidationAction($record, 'revision_requested', [
                'requested_by' => $requestedBy,
                'reason' => $reason,
            ]);

            DB::commit();

            return [
                'success' => true,
                'status' => 'revision',
                'message' => 'Revision requested',
                'requested_by' => $requestedBy,
                'requested_at' => now(),
                'reason' => $reason,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Revision request failed', [
                'record_id' => $record->id,
                'model' => class_basename($record),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get validation statistics
     */
    public function getValidationStats(string $modelClass = null, int $days = 30): array
    {
        try {
            $query = AuditLog::where('action', 'LIKE', '%validation%')
                ->orWhere('action', 'LIKE', '%approved%')
                ->orWhere('action', 'LIKE', '%rejected%')
                ->where('created_at', '>=', now()->subDays($days));

            if ($modelClass) {
                $query->where('model_type', $modelClass);
            }

            $logs = $query->get();

            $stats = [
                'total_submissions' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0,
                'auto_approved' => 0,
                'approval_rate' => 0,
                'average_approval_time' => 0,
                'daily_breakdown' => [],
            ];

            foreach ($logs as $log) {
                $changes = json_decode($log->changes, true) ?? [];
                $date = $log->created_at->format('Y-m-d');

                if (!isset($stats['daily_breakdown'][$date])) {
                    $stats['daily_breakdown'][$date] = [
                        'submissions' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                    ];
                }

                switch ($log->action) {
                    case 'submitted':
                        $stats['total_submissions']++;
                        $stats['daily_breakdown'][$date]['submissions']++;
                        break;
                    case 'approved':
                        $stats['approved']++;
                        $stats['daily_breakdown'][$date]['approved']++;
                        if ($changes['auto_approved'] ?? false) {
                            $stats['auto_approved']++;
                        }
                        break;
                    case 'rejected':
                        $stats['rejected']++;
                        $stats['daily_breakdown'][$date]['rejected']++;
                        break;
                }
            }

            // Calculate approval rate
            $total = $stats['approved'] + $stats['rejected'];
            $stats['approval_rate'] = $total > 0 ? round(($stats['approved'] / $total) * 100, 2) : 0;

            return [
                'success' => true,
                'data' => $stats,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get validation statistics', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get pending validations for user
     */
    public function getPendingValidations(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $user = User::find($userId);

            if (!$user) {
                throw new Exception('User not found');
            }

            $pendingItems = [];

            // Check user roles and permissions
            $canApprove = $user->hasAnyRole(['supervisor', 'manager', 'admin']);

            if ($canApprove) {
                // Get pending items based on user role
                foreach (['Tindakan', 'PendapatanHarian', 'PengeluaranHarian'] as $model) {
                    $modelClass = "App\\Models\\$model";
                    if (class_exists($modelClass)) {
                        $items = $modelClass::where('status_validasi', 'pending')
                            ->with(['user', 'pasien', 'jenisTindakan', 'pendapatan', 'pengeluaran'])
                            ->orderBy('submitted_at', 'desc')
                            ->get();

                        foreach ($items as $item) {
                            $pendingItems[] = [
                                'id' => $item->id,
                                'model' => $model,
                                'title' => $this->getItemTitle($item),
                                'submitted_by' => $item->user->name ?? 'Unknown',
                                'submitted_at' => $item->submitted_at,
                                'amount' => $item->nominal ?? $item->tarif ?? 0,
                                'priority' => $this->calculatePriority($item),
                                'days_pending' => $item->submitted_at ? now()->diffInDays($item->submitted_at) : 0,
                            ];
                        }
                    }
                }
            }

            // Sort by priority and date
            usort($pendingItems, function ($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return $b['days_pending'] <=> $a['days_pending'];
                }
                return $b['priority'] <=> $a['priority'];
            });

            return [
                'success' => true,
                'data' => $pendingItems,
                'total' => count($pendingItems),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get pending validations', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if record should be auto-approved
     */
    protected function shouldAutoApprove(Model $record, array $rules): bool
    {
        $threshold = $rules['auto_approve_threshold'] ?? 0;
        $amount = $record->nominal ?? $record->tarif ?? 0;

        return $amount <= $threshold;
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(Model $record, array $requiredFields): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!$record->$field) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }
    }

    /**
     * Notify approvers
     */
    protected function notifyApprovers(Model $record, string $action): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $amount = $record->nominal ?? $record->tarif ?? 0;

            $message = match ($action) {
                'submitted' => "🔔 *Validasi Diperlukan*\n\n📝 {$modelName}: {$title}\n💰 Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n👤 Diajukan oleh: " . ($record->user->name ?? 'Unknown') . "\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'approved' => "✅ *Approved*\n\n📝 {$modelName}: {$title}\n💰 Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n✅ Disetujui pada: " . now()->format('d/m/Y H:i'),
                default => "ℹ️ Update: {$modelName} - {$title}"
            };

            // Send to approvers (supervisors and managers)
            $approvers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['supervisor', 'manager']);
            })->get();

            foreach ($approvers as $approver) {
                $this->telegramService->sendMessage($approver->id, $message);
            }

        } catch (Exception $e) {
            Log::error('Failed to notify approvers', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify submitter
     */
    protected function notifySubmitter(Model $record, string $action, string $reason = ''): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $submitterId = $record->submitted_by ?? $record->user_id ?? $record->input_by;

            if (!$submitterId) {
                return;
            }

            $message = match ($action) {
                'approved' => "✅ *Disetujui*\n\n📝 {$modelName}: {$title}\n✅ Status: Disetujui\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'rejected' => "❌ *Ditolak*\n\n📝 {$modelName}: {$title}\n❌ Status: Ditolak\n📝 Alasan: {$reason}\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                'revision_requested' => "🔄 *Revisi Diperlukan*\n\n📝 {$modelName}: {$title}\n🔄 Status: Perlu Revisi\n📝 Catatan: {$reason}\n📅 Tanggal: " . now()->format('d/m/Y H:i'),
                default => "ℹ️ Update: {$modelName} - {$title}"
            };

            $this->telegramService->sendMessage($submitterId, $message);

        } catch (Exception $e) {
            Log::error('Failed to notify submitter', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get item title for display
     */
    protected function getItemTitle(Model $record): string
    {
        $modelName = class_basename($record);

        return match ($modelName) {
            'Tindakan' => ($record->jenisTindakan->nama ?? 'Tindakan') . ' - ' . ($record->pasien->nama ?? 'Pasien'),
            'PendapatanHarian' => ($record->pendapatan->nama_pendapatan ?? 'Pendapatan') . ' - ' . $record->tanggal_input,
            'PengeluaranHarian' => ($record->pengeluaran->nama_pengeluaran ?? 'Pengeluaran') . ' - ' . $record->tanggal_input,
            default => $modelName . ' #' . $record->id,
        };
    }

    /**
     * Calculate priority based on amount and age
     */
    protected function calculatePriority(Model $record): int
    {
        $amount = $record->nominal ?? $record->tarif ?? 0;
        $daysPending = $record->submitted_at ? now()->diffInDays($record->submitted_at) : 0;

        $priority = 0;

        // Amount-based priority
        if ($amount >= 1000000) {
            $priority += 5;
        } elseif ($amount >= 500000) {
            $priority += 3;
        } elseif ($amount >= 100000) {
            $priority += 1;
        }

        // Age-based priority
        if ($daysPending >= 7) {
            $priority += 5;
        } elseif ($daysPending >= 3) {
            $priority += 3;
        } elseif ($daysPending >= 1) {
            $priority += 1;
        }

        return $priority;
    }

    /**
     * Send real-time notification
     */
    protected function sendRealTimeNotification(Model $record, string $action): void
    {
        try {
            $modelName = class_basename($record);
            $title = $this->getItemTitle($record);
            $amount = $record->nominal ?? $record->tarif ?? 0;

            $notificationData = match ($action) {
                'validation_submitted' => [
                    'title' => '📤 Validasi Diajukan',
                    'message' => "{$modelName}: {$title} - Menunggu validasi",
                    'priority' => $amount > 500000 ? 'high' : 'medium',
                ],
                'validation_approved' => [
                    'title' => '✅ Validasi Disetujui',
                    'message' => "{$modelName}: {$title} - Disetujui",
                    'priority' => 'medium',
                ],
                'validation_rejected' => [
                    'title' => '❌ Validasi Ditolak',
                    'message' => "{$modelName}: {$title} - Ditolak",
                    'priority' => 'high',
                ],
                'validation_revision' => [
                    'title' => '🔄 Revisi Diperlukan',
                    'message' => "{$modelName}: {$title} - Perlu revisi",
                    'priority' => 'medium',
                ],
                default => [
                    'title' => 'Notifikasi Validasi',
                    'message' => "{$modelName}: {$title}",
                    'priority' => 'medium',
                ],
            };

            // Send to submitter
            $submitterId = $record->submitted_by ?? $record->user_id ?? $record->input_by;
            if ($submitterId) {
                $this->notificationService->sendRealTimeNotification(
                    $submitterId,
                    $action,
                    $notificationData['title'],
                    $notificationData['message'],
                    ['record_id' => $record->id, 'model' => $modelName, 'amount' => $amount],
                    $notificationData['priority']
                );
            }

            // Send to approvers if needed
            if ($action === 'validation_submitted') {
                $approvers = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['supervisor', 'manager']);
                })->get();

                foreach ($approvers as $approver) {
                    $this->notificationService->sendRealTimeNotification(
                        $approver->id,
                        $action,
                        $notificationData['title'],
                        $notificationData['message'],
                        ['record_id' => $record->id, 'model' => $modelName, 'amount' => $amount],
                        $notificationData['priority']
                    );
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to send real-time notification', [
                'record_id' => $record->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log validation action
     */
    protected function logValidationAction(Model $record, string $action, array $details): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($record),
                'model_id' => $record->id,
                'changes' => json_encode($details),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'medium',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log validation action', [
                'error' => $e->getMessage(),
                'action' => $action,
                'record_id' => $record->id,
            ]);
        }
    }
}