<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncNewUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync {--force : Force sync even if users exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync new users added via admin panel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing new users from seeder...');
        
        try {
            // Run only the NewUsersSeeder
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\NewUsersSeeder',
                '--force' => $this->option('force')
            ]);
            
            $this->info('✅ New users synced successfully!');
            $this->newLine();
            $this->info('💡 Tip: After adding users via admin, update NewUsersSeeder.php and run this command');
            
        } catch (\Exception $e) {
            $this->error('❌ Error syncing users: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
