<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedUserAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-users 
                            {--dry-run : Show what would be cleaned up without making changes}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned user accounts that should have been soft deleted with their pegawai';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing User-Pegawai Relationship Integrity...');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        // Step 1: Find orphaned users from deleted pegawai
        $orphanedFromDeleted = $this->findOrphanedUsersFromDeletedPegawai();
        
        // Step 2: Find users with NULL pegawai_id
        $nullPegawaiUsers = $this->findNullPegawaiIdUsers();
        
        // Step 3: Find truly orphaned accounts
        $trulyOrphaned = $this->findTrulyOrphanedUsers();

        // Display summary
        $this->displaySummary($orphanedFromDeleted, $nullPegawaiUsers, $trulyOrphaned);

        if ($orphanedFromDeleted->count() === 0 && $nullPegawaiUsers->count() === 0 && $trulyOrphaned->count() === 0) {
            $this->info('✅ No orphaned user accounts found. System is clean!');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - No changes will be made');
            return 0;
        }

        if (!$isForced && !$this->confirm('Do you want to proceed with cleanup?')) {
            $this->info('Cleanup cancelled.');
            return 0;
        }

        // Perform cleanup
        $results = $this->performCleanup($orphanedFromDeleted, $nullPegawaiUsers, $trulyOrphaned);
        
        $this->displayResults($results);
        
        return 0;
    }

    /**
     * Find users that should have been soft deleted when their pegawai were deleted
     */
    private function findOrphanedUsersFromDeletedPegawai()
    {
        $deletedPegawaiIds = Pegawai::onlyTrashed()->pluck('id');
        
        return User::whereIn('pegawai_id', $deletedPegawaiIds)
                  ->whereNull('deleted_at')
                  ->with(['role', 'pegawai' => function($query) {
                      $query->withTrashed();
                  }])
                  ->get();
    }

    /**
     * Find users with NULL pegawai_id
     */
    private function findNullPegawaiIdUsers()
    {
        return User::whereNull('pegawai_id')
                  ->whereNotNull('username')
                  ->where('username', '!=', '')
                  ->with('role')
                  ->get();
    }

    /**
     * Find truly orphaned users (no pegawai relationship and can't be matched)
     */
    private function findTrulyOrphanedUsers()
    {
        $nullPegawaiUsers = $this->findNullPegawaiIdUsers();
        $trulyOrphaned = collect();

        foreach ($nullPegawaiUsers as $user) {
            $hasMatchingPegawai = false;
            
            // Try to find matching pegawai by username
            if ($user->username) {
                $matchingPegawai = Pegawai::withTrashed()
                                         ->where('username', $user->username)
                                         ->first();
                if ($matchingPegawai) {
                    $hasMatchingPegawai = true;
                }
            }
            
            // Try to find matching pegawai by name
            if (!$hasMatchingPegawai && $user->name) {
                $matchingPegawai = Pegawai::withTrashed()
                                         ->where('nama_lengkap', $user->name)
                                         ->first();
                if ($matchingPegawai) {
                    $hasMatchingPegawai = true;
                }
            }
            
            if (!$hasMatchingPegawai) {
                $trulyOrphaned->push($user);
            }
        }

        return $trulyOrphaned;
    }

    /**
     * Display summary of issues found
     */
    private function displaySummary($orphanedFromDeleted, $nullPegawaiUsers, $trulyOrphaned)
    {
        $this->info('📊 SUMMARY OF ISSUES FOUND:');
        $this->newLine();

        // Orphaned from deleted pegawai
        if ($orphanedFromDeleted->count() > 0) {
            $this->error("❌ CRITICAL: {$orphanedFromDeleted->count()} users should be soft deleted (pegawai deleted):");
            foreach ($orphanedFromDeleted as $user) {
                $pegawai = $user->pegawai;
                $this->line("   • User #{$user->id}: {$user->name} ({$user->username}) - Role: {$user->role?->name}");
                $this->line("     Pegawai #{$pegawai->id}: {$pegawai->nama_lengkap} deleted {$pegawai->deleted_at}");
            }
            $this->newLine();
        }

        // NULL pegawai_id users
        if ($nullPegawaiUsers->count() > 0) {
            $this->warn("⚠️  WARNING: {$nullPegawaiUsers->count()} users with NULL pegawai_id:");
            foreach ($nullPegawaiUsers as $user) {
                $this->line("   • User #{$user->id}: {$user->name} ({$user->username}) - Role: {$user->role?->name}");
            }
            $this->newLine();
        }

        // Truly orphaned
        if ($trulyOrphaned->count() > 0) {
            $this->error("🚨 ORPHANED: {$trulyOrphaned->count()} users with no matching pegawai:");
            foreach ($trulyOrphaned as $user) {
                $this->line("   • User #{$user->id}: {$user->name} ({$user->username}) - Role: {$user->role?->name}");
            }
            $this->newLine();
        }
    }

    /**
     * Perform the actual cleanup
     */
    private function performCleanup($orphanedFromDeleted, $nullPegawaiUsers, $trulyOrphaned)
    {
        $results = [
            'soft_deleted' => 0,
            'linked' => 0,
            'marked_orphaned' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        
        try {
            // Fix orphaned users from deleted pegawai
            foreach ($orphanedFromDeleted as $user) {
                $pegawai = $user->pegawai;
                $user->update(['deleted_at' => $pegawai->deleted_at]);
                $results['soft_deleted']++;
                
                $this->info("✅ Soft deleted user #{$user->id}: {$user->name}");
            }

            // Try to link NULL pegawai_id users
            foreach ($nullPegawaiUsers as $user) {
                $matchingPegawai = null;
                
                // Try by username first
                if ($user->username) {
                    $matchingPegawai = Pegawai::withTrashed()
                                             ->where('username', $user->username)
                                             ->first();
                }
                
                // Try by name if not found
                if (!$matchingPegawai && $user->name) {
                    $matchingPegawai = Pegawai::withTrashed()
                                             ->where('nama_lengkap', $user->name)
                                             ->first();
                }
                
                if ($matchingPegawai) {
                    $user->update(['pegawai_id' => $matchingPegawai->id]);
                    
                    // If pegawai is deleted, soft delete user too
                    if ($matchingPegawai->deleted_at && !$user->deleted_at) {
                        $user->update(['deleted_at' => $matchingPegawai->deleted_at]);
                    }
                    
                    $results['linked']++;
                    $this->info("🔗 Linked user #{$user->id}: {$user->name} to pegawai #{$matchingPegawai->id}");
                }
            }

            // Mark truly orphaned users
            foreach ($trulyOrphaned as $user) {
                // For now, we'll just log them. In future, might want to soft delete or flag them
                $results['marked_orphaned']++;
                $this->warn("🚨 Marked as orphaned: User #{$user->id}: {$user->name}");
            }

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            $results['errors'][] = $e->getMessage();
            $this->error("❌ Error during cleanup: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Display cleanup results
     */
    private function displayResults($results)
    {
        $this->newLine();
        $this->info('🎯 CLEANUP RESULTS:');
        $this->info("✅ Soft deleted: {$results['soft_deleted']} users");
        $this->info("🔗 Linked to pegawai: {$results['linked']} users");
        $this->info("🚨 Marked as orphaned: {$results['marked_orphaned']} users");
        
        if (!empty($results['errors'])) {
            $this->error("❌ Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->error("   • $error");
            }
        }
        
        $this->newLine();
        $this->info('🏥 User-Pegawai relationship cleanup completed!');
    }
}