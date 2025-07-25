<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LoggingService;
use Illuminate\Support\Facades\Log;

class CleanupLogsCommand extends Command
{
    protected $signature = 'dokterku:cleanup-logs 
                           {--days=30 : Number of days to keep logs}
                           {--type=all : Type of logs to cleanup (all, activity, error, security, performance)}
                           {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Cleanup old log entries from the database';

    public function handle()
    {
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        if ($days < 1) {
            $this->error('Days must be a positive number');
            return 1;
        }

        $this->info("🧹 Starting log cleanup process...");
        $this->info("📅 Keeping logs newer than {$days} days");
        $this->info("📊 Log type: {$type}");

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No actual deletion will occur");
        }

        $loggingService = new LoggingService();
        
        try {
            if ($dryRun) {
                $this->performDryRun($days, $type);
            } else {
                $deleted = $loggingService->cleanupOldLogs($days);
                $this->displayResults($deleted);
            }
        } catch (\Exception $e) {
            $this->error("❌ Error during cleanup: " . $e->getMessage());
            Log::error('Log cleanup failed', [
                'error' => $e->getMessage(),
                'days' => $days,
                'type' => $type,
            ]);
            return 1;
        }

        $this->info("✅ Log cleanup completed successfully!");
        return 0;
    }

    protected function performDryRun(int $days, string $type): void
    {
        $cutoffDate = now()->subDays($days);
        
        $this->info("🔍 Analyzing logs to be deleted (created before {$cutoffDate})...");
        
        $counts = [];
        
        if ($type === 'all' || $type === 'activity') {
            $counts['activity'] = \App\Models\AuditLog::where('created_at', '<', $cutoffDate)->count();
        }
        
        if ($type === 'all' || $type === 'error') {
            $counts['error'] = \DB::table('error_logs')->where('created_at', '<', $cutoffDate)->count();
        }
        
        if ($type === 'all' || $type === 'security') {
            $securityCutoff = now()->subDays($days * 2); // Keep security logs longer
            $counts['security'] = \DB::table('security_logs')->where('created_at', '<', $securityCutoff)->count();
        }
        
        if ($type === 'all' || $type === 'performance') {
            $counts['performance'] = \DB::table('performance_logs')->where('created_at', '<', $cutoffDate)->count();
        }

        $this->table(
            ['Log Type', 'Records to Delete'],
            collect($counts)->map(function ($count, $type) {
                return [
                    'type' => ucfirst($type),
                    'count' => number_format($count),
                ];
            })->values()->toArray()
        );

        $total = array_sum($counts);
        $this->info("📊 Total records to delete: " . number_format($total));
    }

    protected function displayResults(array $deleted): void
    {
        $this->info("🗑️  Cleanup Results:");
        
        $this->table(
            ['Log Type', 'Records Deleted'],
            [
                ['Activity Logs', number_format($deleted['activity'])],
                ['Error Logs', number_format($deleted['error'])],
                ['Security Logs', number_format($deleted['security'])],
                ['Performance Logs', number_format($deleted['performance'])],
            ]
        );

        $total = array_sum($deleted);
        $this->info("📊 Total records deleted: " . number_format($total));
        
        if ($total > 0) {
            $this->info("💾 Database space has been freed up");
        } else {
            $this->comment("ℹ️  No old logs found to delete");
        }
    }
}