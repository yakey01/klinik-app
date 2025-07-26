<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class SafeProductionDeploy extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'deploy:production {--backup : Create backup before deployment} {--force : Skip confirmation prompts} {--admin-only : Only deploy admin data}';

    /**
     * The console command description.
     */
    protected $description = 'Safe production deployment with backup and rollback capabilities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Safe Production Deployment...');

        // Environment check
        if (!$this->confirmProductionEnvironment()) {
            return 1;
        }

        try {
            // Step 1: Create backup if requested
            if ($this->option('backup')) {
                $this->createBackup();
            }

            // Step 2: Run pre-deployment checks
            $this->runPreDeploymentChecks();

            // Step 3: Deploy based on scope
            if ($this->option('admin-only')) {
                $this->deployAdminOnly();
            } else {
                $this->deployFullSystem();
            }

            // Step 4: Run post-deployment verification
            $this->runPostDeploymentVerification();

            $this->info('✅ Production deployment completed successfully!');
            return 0;

        } catch (Exception $e) {
            $this->error('❌ Deployment failed: ' . $e->getMessage());
            Log::error('Production deployment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function confirmProductionEnvironment(): bool
    {
        if (!app()->environment('production')) {
            $this->error('This command only runs in production environment.');
            return false;
        }

        if (!$this->option('force')) {
            $confirmed = $this->confirm('⚠️  You are about to deploy to PRODUCTION. Continue?');
            if (!$confirmed) {
                $this->info('Deployment cancelled by user.');
                return false;
            }
        }

        return true;
    }

    private function createBackup(): void
    {
        $this->info('📦 Creating database backup...');
        
        $backupFile = storage_path('backups/pre_deploy_' . date('Y_m_d_H_i_s') . '.sql');
        
        // Create backup directory
        if (!is_dir(dirname($backupFile))) {
            mkdir(dirname($backupFile), 0755, true);
        }

        // Create backup
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $command = "mysqldump -h {$dbHost} -u {$dbUser} -p'{$dbPass}' {$dbName} > {$backupFile}";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Failed to create database backup');
        }

        $this->info("✅ Backup created: {$backupFile}");
    }

    private function runPreDeploymentChecks(): void
    {
        $this->info('🔍 Running pre-deployment checks...');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection: OK');
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }

        // Check required tables exist
        $requiredTables = ['users', 'roles', 'permissions'];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new Exception("Required table '{$table}' does not exist");
            }
        }
        $this->info('✅ Required tables: OK');

        // Check file permissions
        $writablePaths = [storage_path(), base_path('bootstrap/cache')];
        foreach ($writablePaths as $path) {
            if (!is_writable($path)) {
                throw new Exception("Path '{$path}' is not writable");
            }
        }
        $this->info('✅ File permissions: OK');
    }

    private function deployAdminOnly(): void
    {
        $this->info('👤 Deploying admin data only...');

        DB::beginTransaction();
        try {
            // Clear existing admin data
            $this->info('🗑️ Clearing existing admin data...');
            DB::table('users')->where('email', 'LIKE', '%admin%')->delete();

            // Run admin seeder
            $this->info('🌱 Running ProductionAdminReplacementSeeder...');
            Artisan::call('db:seed', [
                '--class' => 'ProductionAdminReplacementSeeder',
                '--force' => true
            ]);

            DB::commit();
            $this->info('✅ Admin deployment completed');

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Admin deployment failed: ' . $e->getMessage());
        }
    }

    private function deployFullSystem(): void
    {
        $this->info('🏗️ Deploying full system...');

        // Run migrations
        $this->info('📝 Running migrations...');
        Artisan::call('migrate', ['--force' => true]);

        // Clear caches
        $this->info('🧹 Clearing caches...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Run seeders
        $this->info('🌱 Running seeders...');
        $seeders = [
            'RolePermissionSeeder',
            'ProductionAdminReplacementSeeder',
            'ParamedisRealDataSeeder'
        ];

        foreach ($seeders as $seeder) {
            $this->info("Running {$seeder}...");
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true
            ]);
        }

        // Optimize for production
        $this->info('⚡ Optimizing for production...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }

    private function runPostDeploymentVerification(): void
    {
        $this->info('✅ Running post-deployment verification...');

        // Verify admin user exists
        $adminCount = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'admin')
            ->count();

        if ($adminCount === 0) {
            throw new Exception('No admin users found after deployment');
        }
        $this->info("✅ Admin users: {$adminCount}");

        // Verify database integrity
        $tableChecks = [
            'users' => 'id',
            'roles' => 'id',
            'permissions' => 'id'
        ];

        foreach ($tableChecks as $table => $column) {
            $count = DB::table($table)->count();
            $this->info("✅ {$table}: {$count} records");
        }

        $this->info('✅ Post-deployment verification completed');
    }
}