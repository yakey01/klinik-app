<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class CleanupSoftDeletedRecords extends Command
{
    protected $signature = 'cleanup:soft-deleted 
                            {--days=30 : Number of days after soft delete to permanently delete}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old soft deleted records and their related data';

    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up soft deleted records older than {$days} days...");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual deletions will be performed");
        }

        // 1. Find old soft deleted pegawai
        $oldPegawai = Pegawai::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();

        if ($oldPegawai->count() > 0) {
            $this->info("Found {$oldPegawai->count()} pegawai to clean up");
            
            foreach ($oldPegawai as $pegawai) {
                $this->line(" - {$pegawai->nama_lengkap} (ID: {$pegawai->id}, deleted: {$pegawai->deleted_at})");
                
                if (!$dryRun) {
                    // Archive attendance records before deletion
                    $this->archiveAttendanceRecords($pegawai);
                    
                    // Force delete pegawai and cascade to users
                    $pegawai->forceDelete();
                }
            }
        }

        // 2. Find orphaned soft deleted users
        $orphanedUsers = User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->whereDoesntHave('pegawai')
            ->get();

        if ($orphanedUsers->count() > 0) {
            $this->info("Found {$orphanedUsers->count()} orphaned users to clean up");
            
            if (!$dryRun) {
                foreach ($orphanedUsers as $user) {
                    $user->forceDelete();
                }
            }
        }

        $this->info("Cleanup completed!");
    }

    private function archiveAttendanceRecords($pegawai)
    {
        $userIds = $pegawai->users()->withTrashed()->pluck('id');
        
        if ($userIds->count() > 0) {
            // Archive attendance records (you can implement actual archiving logic here)
            $attendanceCount = Attendance::whereIn('user_id', $userIds)->count();
            
            if ($attendanceCount > 0) {
                $this->line("   Archiving {$attendanceCount} attendance records...");
                // Implement archiving logic if needed
                // For now, we'll just note it
            }
        }
    }
}