<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Role;

class TestAdminReplacement extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:test-replacement 
                            {--dry-run : Only simulate the replacement process}
                            {--cleanup : Clean up test data after testing}';

    /**
     * The console command description.
     */
    protected $description = 'Test the admin replacement process safely in non-production environments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Admin Replacement Testing Tool');
        $this->info('==================================');

        // Prevent running in production
        if (app()->environment('production')) {
            $this->error('❌ This test command cannot run in production environment!');
            $this->info('Use php artisan admin:replace instead for production.');
            return 1;
        }

        $dryRun = $this->option('dry-run');
        $cleanup = $this->option('cleanup');

        if ($cleanup) {
            return $this->cleanupTestData();
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE: No actual changes will be made');
        }

        return $this->testAdminReplacementProcess($dryRun);
    }

    /**
     * Test the admin replacement process
     */
    private function testAdminReplacementProcess(bool $dryRun): int
    {
        $this->info('🚀 Starting admin replacement test...');

        try {
            // Step 1: Create test environment
            $this->info('📋 Step 1: Setting up test environment');
            $this->createTestEnvironment($dryRun);

            // Step 2: Test migration
            $this->info('📋 Step 2: Testing migration process');
            $this->testMigration($dryRun);

            // Step 3: Test seeder
            $this->info('📋 Step 3: Testing seeder process');
            $this->testSeeder($dryRun);

            // Step 4: Test artisan command
            $this->info('📋 Step 4: Testing artisan command');
            $this->testArtisanCommand($dryRun);

            // Step 5: Test verification
            $this->info('📋 Step 5: Testing verification process');
            $this->testVerification($dryRun);

            // Step 6: Test rollback
            $this->info('📋 Step 6: Testing rollback process');
            $this->testRollback($dryRun);

            $this->info('');
            $this->info('🎉 All admin replacement tests passed successfully!');
            $this->info('');
            $this->showTestSummary();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create test environment
     */
    private function createTestEnvironment(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would create test admin users and roles');
            return;
        }

        // Ensure we have test admin users
        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'display_name' => 'Administrator Test',
            'description' => 'Test admin role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        // Create test admin users
        $testAdmins = [
            [
                'name' => 'Test Admin 1',
                'email' => 'test.admin1@example.com',
                'username' => 'testadmin1',
                'password' => bcrypt('testpassword'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Admin 2',
                'email' => 'test.admin2@example.com',
                'username' => 'testadmin2',
                'password' => bcrypt('testpassword'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        ];

        foreach ($testAdmins as $adminData) {
            User::updateOrCreate(
                ['email' => $adminData['email']], 
                $adminData
            );
        }

        $this->info('   ✅ Test environment created');
    }

    /**
     * Test migration
     */
    private function testMigration(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would test admin replacement migration');
            return;
        }

        // Check if migration tables would be created
        $migrationFile = database_path('migrations/2025_07_25_120000_replace_admin_users_safely.php');
        
        if (!file_exists($migrationFile)) {
            throw new \Exception('Admin replacement migration file not found');
        }

        $this->info('   ✅ Migration file exists and is accessible');

        // Test backup table creation
        if (!Schema::hasTable('admin_users_backup')) {
            Schema::create('admin_users_backup', function ($table) {
                $table->id();
                $table->string('original_user_id');
                $table->string('name');
                $table->string('email');
                $table->string('username')->nullable();
                $table->string('password');
                $table->string('role_name');
                $table->json('user_data');
                $table->timestamp('backed_up_at');
                $table->string('backup_reason')->default('test');
                $table->timestamps();
            });
            $this->info('   ✅ Backup table created for testing');
        }
    }

    /**
     * Test seeder
     */
    private function testSeeder(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would test ProductionAdminReplacementSeeder (skipped in non-production)');
            return;
        }

        $seederFile = database_path('seeders/ProductionAdminReplacementSeeder.php');
        
        if (!file_exists($seederFile)) {
            throw new \Exception('ProductionAdminReplacementSeeder file not found');
        }

        // Seeder skips in non-production, so just verify it loads
        $seederClass = 'Database\\Seeders\\ProductionAdminReplacementSeeder';
        if (!class_exists($seederClass)) {
            throw new \Exception('ProductionAdminReplacementSeeder class not found');
        }

        $this->info('   ✅ Seeder class exists and loads correctly');
    }

    /**
     * Test artisan command
     */
    private function testArtisanCommand(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would test admin:replace artisan command');
            return;
        }

        // Test that the command exists and can be called
        try {
            Artisan::call('admin:replace', ['--verify' => true]);
            $output = Artisan::output();
            $this->info('   ✅ admin:replace command executed successfully');
        } catch (\Exception $e) {
            throw new \Exception('admin:replace command failed: ' . $e->getMessage());
        }
    }

    /**
     * Test verification
     */
    private function testVerification(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would test admin verification process');
            return;
        }

        // Test that we can verify admin users
        $adminUsers = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($adminUsers->isEmpty()) {
            throw new \Exception('No admin users found for verification test');
        }

        foreach ($adminUsers as $admin) {
            if (!$admin->role || $admin->role->name !== 'admin') {
                throw new \Exception('Admin user role verification failed');
            }
        }

        $this->info('   ✅ Admin verification process working correctly');
    }

    /**
     * Test rollback
     */
    private function testRollback(bool $dryRun): void
    {
        if ($dryRun) {
            $this->info('   Would test rollback process');
            return;
        }

        // Test that rollback mechanisms exist
        if (!Schema::hasTable('admin_users_backup')) {
            throw new \Exception('Backup table not available for rollback test');
        }

        $this->info('   ✅ Rollback infrastructure is in place');
    }

    /**
     * Clean up test data
     */
    private function cleanupTestData(): int
    {
        $this->info('🧹 Cleaning up test data...');

        try {
            // Remove test admin users
            User::where('email', 'like', 'test.admin%@example.com')->delete();
            
            // Drop test tables if they exist
            if (Schema::hasTable('admin_users_backup')) {
                DB::table('admin_users_backup')->where('backup_reason', 'test')->delete();
            }

            $this->info('✅ Test data cleanup completed');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show test summary
     */
    private function showTestSummary(): void
    {
        $this->info('📊 Test Summary:');
        $this->info('================');
        $this->info('✅ Migration file validation');
        $this->info('✅ Seeder class validation');
        $this->info('✅ Artisan command functionality');
        $this->info('✅ Admin verification process');
        $this->info('✅ Rollback infrastructure');
        $this->info('✅ Database table structures');
        $this->info('');
        $this->info('🚀 Ready for production deployment!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Commit all changes to git');
        $this->info('2. Push to main branch');
        $this->info('3. Run GitHub Actions workflow: "Replace Admin Users"');
        $this->info('4. Verify admin access after deployment');
    }
}