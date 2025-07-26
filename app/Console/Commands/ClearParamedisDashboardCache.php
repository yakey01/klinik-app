<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Cache;

class ClearParamedisDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-paramedis-dashboard 
                            {--user=* : Specific user IDs to clear cache for}
                            {--all : Clear cache for all paramedis users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear dashboard cache for paramedis users to force fresh data loading';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Clearing Paramedis Dashboard Cache...');
        
        $userIds = $this->option('user');
        $clearAll = $this->option('all');
        
        if (!$clearAll && empty($userIds)) {
            $this->error('Please specify --all or --user=ID option');
            return 1;
        }
        
        $clearedCount = 0;
        
        if ($clearAll) {
            // Get all paramedis users
            $paramedisUsers = Pegawai::where('jenis_pegawai', 'Paramedis')
                ->whereNotNull('user_id')
                ->pluck('user_id', 'nama_lengkap');
            
            foreach ($paramedisUsers as $name => $userId) {
                $cacheKey = "paramedis_dashboard_stats_{$userId}";
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $clearedCount++;
                    $this->info("✅ Cleared cache for {$name} (User ID: {$userId})");
                } else {
                    $this->line("⏭️  No cache found for {$name} (User ID: {$userId})");
                }
            }
        } else {
            // Clear specific users
            foreach ($userIds as $userId) {
                $cacheKey = "paramedis_dashboard_stats_{$userId}";
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $clearedCount++;
                    $this->info("✅ Cleared cache for User ID: {$userId}");
                } else {
                    $this->warn("⚠️  No cache found for User ID: {$userId}");
                }
            }
        }
        
        $this->newLine();
        $this->info("🎯 Summary: Cleared {$clearedCount} cache entries");
        
        // Note: Additional cache clearing can be added here if needed
        // Cache tags are not supported by the current cache driver
        
        return 0;
    }
}