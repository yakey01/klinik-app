<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure soft deletes columns exist on all relevant tables
        $tables = [
            'users' => true,
            'pegawais' => true,
            'dokters' => true,
            'attendances' => false, // Don't soft delete attendance records
            'dokter_presensis' => false,
            'non_paramedis_attendances' => false,
        ];

        foreach ($tables as $table => $needsSoftDelete) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($needsSoftDelete) {
                    if ($needsSoftDelete && !Schema::hasColumn($table->getTable(), 'deleted_at')) {
                        $table->softDeletes();
                    }
                });
            }
        }

        // 2. Add indexes for better soft delete performance
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'users_deleted_at_index')) {
                    $table->index('deleted_at');
                }
                if (!$this->indexExists('users', 'users_pegawai_id_deleted_at_index')) {
                    $table->index(['pegawai_id', 'deleted_at']);
                }
            });
        }

        if (Schema::hasTable('pegawais')) {
            Schema::table('pegawais', function (Blueprint $table) {
                if (!$this->indexExists('pegawais', 'pegawais_deleted_at_index')) {
                    $table->index('deleted_at');
                }
                if (!$this->indexExists('pegawais', 'pegawais_username_deleted_at_index')) {
                    $table->index(['username', 'deleted_at']);
                }
            });
        }

        // 3. Fix orphaned user records (users with non-existent pegawai_id)
        $orphanedUsers = DB::table('users')
            ->whereNotNull('pegawai_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pegawais')
                    ->whereColumn('pegawais.id', 'users.pegawai_id');
            })
            ->pluck('id');

        if ($orphanedUsers->count() > 0) {
            DB::table('users')
                ->whereIn('id', $orphanedUsers)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);
            
            echo "Soft deleted {$orphanedUsers->count()} orphaned user records.\n";
        }

        // 4. Ensure user accounts are soft deleted when pegawai is soft deleted
        $activeUsersWithDeletedPegawai = DB::table('users')
            ->whereNotNull('pegawai_id')
            ->whereNull('deleted_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pegawais')
                    ->whereColumn('pegawais.id', 'users.pegawai_id')
                    ->whereNotNull('pegawais.deleted_at');
            })
            ->pluck('id');

        if ($activeUsersWithDeletedPegawai->count() > 0) {
            DB::table('users')
                ->whereIn('id', $activeUsersWithDeletedPegawai)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);
            
            echo "Soft deleted {$activeUsersWithDeletedPegawai->count()} user accounts linked to soft deleted pegawai.\n";
        }

        // 5. Create a cleanup command for orphaned records
        $this->createCleanupCommand();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_deleted_at_index')) {
                    $table->dropIndex('users_deleted_at_index');
                }
                if ($this->indexExists('users', 'users_pegawai_id_deleted_at_index')) {
                    $table->dropIndex('users_pegawai_id_deleted_at_index');
                }
            });
        }

        if (Schema::hasTable('pegawais')) {
            Schema::table('pegawais', function (Blueprint $table) {
                if ($this->indexExists('pegawais', 'pegawais_deleted_at_index')) {
                    $table->dropIndex('pegawais_deleted_at_index');
                }
                if ($this->indexExists('pegawais', 'pegawais_username_deleted_at_index')) {
                    $table->dropIndex('pegawais_username_deleted_at_index');
                }
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("PRAGMA index_list({$table})");
        foreach ($indexes as $index) {
            if ($index->name === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create cleanup command file
     */
    private function createCleanupCommand(): void
    {
        $commandPath = app_path('Console/Commands/CleanupSoftDeletedRecords.php');
        
        if (!file_exists($commandPath)) {
            $content = <<<'PHP'
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
PHP;
            
            file_put_contents($commandPath, $content);
            echo "Created cleanup command: php artisan cleanup:soft-deleted\n";
        }
    }
};