<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

class ReplaceAdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:replace 
                            {--force : Force replacement without confirmation}
                            {--rollback : Rollback to previous admin users}
                            {--verify : Only verify current admin setup}
                            {--email= : Admin email (default: admin@dokterku.com)}
                            {--password= : Admin password (default: from env)}
                            {--name= : Admin name (default: Administrator)}';

    /**
     * The console command description.
     */
    protected $description = 'Replace all admin users with new localhost admin user safely';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Admin User Replacement Tool');
        $this->info('=====================================');

        // Handle different modes
        if ($this->option('verify')) {
            return $this->verifyAdminSetup();
        }

        if ($this->option('rollback')) {
            return $this->rollbackAdminUsers();
        }

        // Main replacement flow
        return $this->replaceAdminUsers();
    }

    /**
     * Main admin replacement process
     */
    private function replaceAdminUsers(): int
    {
        // Environment check
        if (!app()->environment('production') && !$this->option('force')) {
            $this->warn('⚠️  This command should only run in production environment.');
            $this->info('Use --force flag to run in ' . app()->environment() . ' environment.');
            return 1;
        }

        // Show current admin users
        $this->showCurrentAdminUsers();

        // Confirmation check
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will replace ALL existing admin users. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            if (!$this->confirm('🔴 FINAL WARNING: This action cannot be easily undone. Are you absolutely sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('🔄 Starting admin user replacement process...');

        try {
            DB::beginTransaction();

            // Step 1: Create database backup
            $this->info('💾 Creating database backup...');
            $backupCreated = $this->createDatabaseBackup();
            
            if (!$backupCreated) {
                $this->warn('⚠️  Could not create database backup, but continuing...');
            } else {
                $this->info('✅ Database backup created successfully');
            }

            // Step 2: Run migration to backup and remove existing admins
            $this->info('🗄️  Running admin cleanup migration...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Admin cleanup migration completed');

            // Step 3: Run seeder to create new admin
            $this->info('👤 Creating new admin user...');
            Artisan::call('db:seed', [
                '--class' => 'ProductionAdminReplacementSeeder',
                '--force' => true
            ]);
            $this->info('✅ New admin user created');

            // Step 4: Verify the new admin
            $this->info('🔍 Verifying new admin setup...');
            $verificationResult = $this->verifyNewAdmin();
            
            if (!$verificationResult) {
                throw new \Exception('New admin verification failed');
            }

            // Step 5: Log successful replacement
            $this->logAdminReplacement('success');

            DB::commit();

            $this->info('');
            $this->info('🎉 Admin replacement completed successfully!');
            $this->info('');
            $this->showNewAdminDetails();
            
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Admin replacement failed: ' . $e->getMessage());
            $this->info('🔄 Database changes have been rolled back.');
            
            // Log the failure
            $this->logAdminReplacement('failed', $e->getMessage());
            
            // Offer rollback option
            if ($this->confirm('Would you like to attempt automatic rollback to previous admin users?')) {
                return $this->rollbackAdminUsers();
            }
            
            return 1;
        }
    }

    /**
     * Show current admin users
     */
    private function showCurrentAdminUsers(): void
    {
        $this->info('📋 Current Admin Users:');
        $this->info('========================');

        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->warn('No admin role found.');
            return;
        }

        $adminUsers = User::where('role_id', $adminRole->id)
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('No admin users found.');
            return;
        }

        $headers = ['ID', 'Name', 'Email', 'Username', 'Created'];
        $rows = [];

        foreach ($adminUsers as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->username ?: 'N/A',
                $user->created_at->format('Y-m-d H:i')
            ];
        }

        $this->table($headers, $rows);
        $this->info('Total: ' . $adminUsers->count() . ' admin users will be replaced');
        $this->info('');
    }

    /**
     * Verify new admin setup
     */
    private function verifyNewAdmin(): bool
    {
        $adminEmail = $this->option('email') ?: env('PRODUCTION_ADMIN_EMAIL', 'admin@dokterku.com');
        
        $admin = User::where('email', $adminEmail)->first();
        
        if (!$admin) {
            $this->error('❌ New admin user not found');
            return false;
        }

        // Check role assignment
        if (!$admin->role || $admin->role->name !== 'admin') {
            $this->error('❌ Admin role not properly assigned');
            return false;
        }

        // Check panel access
        if (method_exists($admin, 'canAccessPanel')) {
            $adminPanel = filament('admin')->getPanel();
            if (!$admin->canAccessPanel($adminPanel)) {
                $this->error('❌ Admin cannot access admin panel');
                return false;
            }
        }

        $this->info('✅ New admin verification passed');
        return true;
    }

    /**
     * Verify current admin setup
     */
    private function verifyAdminSetup(): int
    {
        $this->info('🔍 Verifying Current Admin Setup');
        $this->info('================================');

        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->error('❌ Admin role not found');
            return 1;
        }

        $this->info('✅ Admin role exists: ' . $adminRole->display_name);

        $adminUsers = User::where('role_id', $adminRole->id)->get();
        
        if ($adminUsers->isEmpty()) {
            $this->warn('⚠️  No admin users found');
            return 1;
        }

        $this->info('✅ Found ' . $adminUsers->count() . ' admin users:');
        
        foreach ($adminUsers as $user) {
            $canAccess = 'Unknown';
            if (method_exists($user, 'canAccessPanel')) {
                $adminPanel = filament('admin')->getPanel();
                $canAccess = $user->canAccessPanel($adminPanel) ? 'Yes' : 'No';
            }
            
            $this->info("   - {$user->name} ({$user->email}) - Panel Access: {$canAccess}");
        }

        $this->info('✅ Admin setup verification completed');
        return 0;
    }

    /**
     * Rollback to previous admin users
     */
    private function rollbackAdminUsers(): int
    {
        $this->info('🔄 Admin User Rollback');
        $this->info('=====================');

        if (!Schema::hasTable('admin_users_backup')) {
            $this->error('❌ No backup table found. Cannot rollback.');
            return 1;
        }

        $backupCount = DB::table('admin_users_backup')->count();
        
        if ($backupCount === 0) {
            $this->error('❌ No admin user backups found. Cannot rollback.');
            return 1;
        }

        $this->info("📦 Found {$backupCount} admin users in backup");

        if (!$this->option('force')) {
            if (!$this->confirm('This will restore previous admin users and remove current ones. Continue?')) {
                $this->info('Rollback cancelled.');
                return 0;
            }
        }

        try {
            // Run migration rollback
            $this->info('🔄 Running migration rollback...');
            Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
            
            $this->info('✅ Admin users rollback completed successfully');
            
            // Log the rollback
            $this->logAdminReplacement('rollback');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Rollback failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show new admin details
     */
    private function showNewAdminDetails(): void
    {
        $adminEmail = $this->option('email') ?: env('PRODUCTION_ADMIN_EMAIL', 'admin@dokterku.com');
        $adminPassword = $this->option('password') ?: env('PRODUCTION_ADMIN_PASSWORD', 'dokterku_admin_2024');
        
        $this->info('🔐 New Admin Credentials:');
        $this->info('=========================');
        $this->info('Email: ' . $adminEmail);
        $this->info('Password: ' . $adminPassword);
        $this->info('Admin Panel: https://dokterkuklinik.com/admin');
        $this->info('');
        $this->info('🔒 Please change the password after first login!');
    }

    /**
     * Create database backup
     */
    private function createDatabaseBackup(): bool
    {
        try {
            $backupFile = storage_path('backups/admin_replacement_' . now()->format('Y_m_d_His') . '.sql');
            
            // Create backup directory if it doesn't exist
            if (!file_exists(dirname($backupFile))) {
                mkdir(dirname($backupFile), 0755, true);
            }

            // For MySQL databases
            if (config('database.default') === 'mysql') {
                $dbHost = config('database.connections.mysql.host');
                $dbName = config('database.connections.mysql.database');
                $dbUser = config('database.connections.mysql.username');
                $dbPass = config('database.connections.mysql.password');
                
                $command = "mysqldump -h {$dbHost} -u {$dbUser} -p{$dbPass} {$dbName} > {$backupFile}";
                exec($command, $output, $returnCode);
                
                return $returnCode === 0 && file_exists($backupFile);
            }
            
            return false;

        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log admin replacement activity
     */
    private function logAdminReplacement(string $status, ?string $errorMessage = null): void
    {
        try {
            DB::table('admin_replacement_logs')->insert([
                'action' => 'admin_replace_command',
                'status' => $status,
                'details' => json_encode([
                    'command_options' => $this->options(),
                    'error_message' => $errorMessage,
                    'timestamp' => now()->toISOString(),
                    'environment' => app()->environment(),
                ]),
                'user_agent' => 'AdminReplaceCommand',
                'ip_address' => 'console',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log admin replacement: ' . $e->getMessage());
        }
    }
}