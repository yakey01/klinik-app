<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Widgets\Widget;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManajerApprovalWorkflowWidget extends Widget
{
    protected static string $view = 'filament.manajer.widgets.approval-workflow-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 6;

    public function getViewData(): array
    {
        return [
            'pending_approvals' => $this->getPendingApprovals(),
            'approval_metrics' => $this->getApprovalMetrics(),
            'workflow_status' => $this->getWorkflowStatus(),
            'approval_history' => $this->getApprovalHistory(),
        ];
    }

    private function getPendingApprovals(): array
    {
        $currentMonth = Carbon::now();
        
        // Pending procedures validation
        $pendingProcedures = Tindakan::where('validation_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // Pending revenue validation
        $pendingRevenue = Pendapatan::where('validation_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // Pending expense approvals
        $pendingExpenses = Pengeluaran::where('approval_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // High-priority approvals (older than 3 days)
        $highPriorityApprovals = Tindakan::where('validation_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();
        
        return [
            'procedures' => [
                'items' => $pendingProcedures,
                'count' => $pendingProcedures->count(),
                'priority' => $highPriorityApprovals > 0 ? 'high' : 'normal',
            ],
            'revenue' => [
                'items' => $pendingRevenue,
                'count' => $pendingRevenue->count(),
                'total_amount' => $pendingRevenue->sum('jumlah'),
            ],
            'expenses' => [
                'items' => $pendingExpenses,
                'count' => $pendingExpenses->count(),
                'total_amount' => $pendingExpenses->sum('jumlah'),
            ],
            'high_priority_count' => $highPriorityApprovals,
        ];
    }

    private function getApprovalMetrics(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        
        // Current month approval metrics
        $currentApprovals = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', '!=', 'pending')
            ->count();
            
        $currentValidated = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', 'validated')
            ->count();
            
        $currentRejected = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', 'rejected')
            ->count();
        
        // Approval rate calculations
        $approvalRate = $currentApprovals > 0 ? ($currentValidated / $currentApprovals) * 100 : 0;
        $rejectionRate = $currentApprovals > 0 ? ($currentRejected / $currentApprovals) * 100 : 0;
        
        // Average approval time
        $avgApprovalTime = Tindakan::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->where('validation_status', 'validated')
            ->whereNotNull('validated_at')
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, validated_at)'));
            
        // Approval backlog
        $backlogDays = [
            '0-1' => Tindakan::where('validation_status', 'pending')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->count(),
            '1-3' => Tindakan::where('validation_status', 'pending')
                ->whereBetween('created_at', [Carbon::now()->subDays(3), Carbon::now()->subDay()])
                ->count(),
            '3-7' => Tindakan::where('validation_status', 'pending')
                ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()->subDays(3)])
                ->count(),
            '7+' => Tindakan::where('validation_status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(7))
                ->count(),
        ];
        
        return [
            'approval_rate' => round($approvalRate, 1),
            'rejection_rate' => round($rejectionRate, 1),
            'avg_approval_time' => round($avgApprovalTime ?? 0, 1),
            'total_processed' => $currentApprovals,
            'backlog_distribution' => $backlogDays,
        ];
    }

    private function getWorkflowStatus(): array
    {
        $currentMonth = Carbon::now();
        
        // Workflow efficiency by validator
        $validatorPerformance = User::role('bendahara')
            ->withCount(['validatedTindakan' => function ($query) use ($currentMonth) {
                $query->whereMonth('validated_at', $currentMonth->month)
                    ->whereYear('validated_at', $currentMonth->year);
            }])
            ->orderBy('validated_tindakan_count', 'desc')
            ->take(5)
            ->get();
            
        // Workflow bottlenecks
        $bottlenecks = [];
        
        $oldPending = Tindakan::where('validation_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subDays(5))
            ->count();
            
        if ($oldPending > 10) {
            $bottlenecks[] = [
                'type' => 'validation_delay',
                'message' => 'High number of pending validations older than 5 days',
                'count' => $oldPending,
                'severity' => 'high',
            ];
        }
        
        $highValuePending = Pendapatan::where('validation_status', 'pending')
            ->where('jumlah', '>', 1000000)
            ->count();
            
        if ($highValuePending > 0) {
            $bottlenecks[] = [
                'type' => 'high_value_pending',
                'message' => 'High-value revenue items pending approval',
                'count' => $highValuePending,
                'severity' => 'medium',
            ];
        }
        
        // Workflow automation opportunities
        $automationOpportunities = [
            'auto_approve_threshold' => 500000, // Auto-approve transactions below this amount
            'potential_auto_approvals' => Tindakan::where('validation_status', 'pending')
                ->where('harga', '<', 500000)
                ->count(),
            'time_savings_hours' => 0, // Calculate based on average approval time
        ];
        
        return [
            'validator_performance' => $validatorPerformance,
            'bottlenecks' => $bottlenecks,
            'automation_opportunities' => $automationOpportunities,
            'workflow_health' => $this->calculateWorkflowHealth(),
        ];
    }

    private function getApprovalHistory(): array
    {
        $history = [];
        
        // Last 7 days approval trend
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $dailyApprovals = Tindakan::whereDate('validated_at', $date)
                ->where('validation_status', 'validated')
                ->count();
                
            $dailyRejections = Tindakan::whereDate('validated_at', $date)
                ->where('validation_status', 'rejected')
                ->count();
                
            $history[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'approvals' => $dailyApprovals,
                'rejections' => $dailyRejections,
                'total' => $dailyApprovals + $dailyRejections,
            ];
        }
        
        return $history;
    }

    private function calculateWorkflowHealth(): array
    {
        $totalPending = Tindakan::where('validation_status', 'pending')->count();
        $oldPending = Tindakan::where('validation_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->count();
            
        $backlogRatio = $totalPending > 0 ? ($oldPending / $totalPending) * 100 : 0;
        
        // Health score calculation (0-100)
        $healthScore = 100;
        
        if ($backlogRatio > 30) $healthScore -= 30; // High backlog penalty
        if ($totalPending > 50) $healthScore -= 20; // High volume penalty
        if ($oldPending > 20) $healthScore -= 25; // Old pending penalty
        
        $healthScore = max($healthScore, 0);
        
        $status = 'excellent';
        if ($healthScore < 80) $status = 'good';
        if ($healthScore < 60) $status = 'needs-attention';
        if ($healthScore < 40) $status = 'critical';
        
        return [
            'score' => round($healthScore),
            'status' => $status,
            'total_pending' => $totalPending,
            'old_pending' => $oldPending,
            'backlog_ratio' => round($backlogRatio, 1),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
}