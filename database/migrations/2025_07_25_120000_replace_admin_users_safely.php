<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run in production environment to avoid affecting local development
        if (!app()->environment('production')) {
            Log::info('AdminReplacement: Skipping admin replacement in ' . app()->environment() . ' environment');
            return;
        }

        Log::info('AdminReplacement: Starting admin user replacement process');

        DB::beginTransaction();

        try {
            // Step 1: Create backup table for admin users
            if (!Schema::hasTable('admin_users_backup')) {
                Schema::create('admin_users_backup', function (Blueprint $table) {
                    $table->id();
                    $table->string('original_user_id');
                    $table->string('name');
                    $table->string('email');
                    $table->string('username')->nullable();
                    $table->string('password');
                    $table->string('role_name');
                    $table->json('user_data'); // Full user data backup
                    $table->timestamp('backed_up_at');
                    $table->string('backup_reason')->default('admin_replacement_migration');
                    $table->timestamps();
                });
            }

            // Step 2: Find and backup existing admin users
            $adminRole = Role::where('name', 'admin')->first();
            
            if ($adminRole) {
                $adminUsers = User::where('role_id', $adminRole->id)
                    ->orWhereHas('roles', function ($query) {
                        $query->where('name', 'admin');
                    })
                    ->withTrashed() // Include soft deleted
                    ->get();

                Log::info('AdminReplacement: Found ' . $adminUsers->count() . ' admin users to backup');

                // Backup each admin user
                foreach ($adminUsers as $user) {
                    DB::table('admin_users_backup')->insert([
                        'original_user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'password' => $user->password,
                        'role_name' => 'admin',
                        'user_data' => json_encode($user->toArray()),
                        'backed_up_at' => now(),
                        'backup_reason' => 'admin_replacement_migration',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('AdminReplacement: Backed up admin user: ' . $user->email);
                }

                // Step 3: Soft delete existing admin users (safer than hard delete)
                $deletedCount = 0;
                foreach ($adminUsers as $user) {
                    if (!$user->trashed()) {
                        $user->delete(); // Soft delete
                        $deletedCount++;
                        Log::info('AdminReplacement: Soft deleted admin user: ' . $user->email);
                    }
                }

                Log::info('AdminReplacement: Soft deleted ' . $deletedCount . ' admin users');
            } else {
                Log::warning('AdminReplacement: No admin role found');
            }

            // Step 4: Create activity log table if not exists
            if (!Schema::hasTable('admin_replacement_logs')) {
                Schema::create('admin_replacement_logs', function (Blueprint $table) {
                    $table->id();
                    $table->string('action');
                    $table->string('status');
                    $table->json('details')->nullable();
                    $table->string('user_agent')->nullable();
                    $table->string('ip_address')->nullable();
                    $table->timestamps();
                });
            }

            // Log the migration completion
            DB::table('admin_replacement_logs')->insert([
                'action' => 'admin_users_backed_up_and_removed',
                'status' => 'success',
                'details' => json_encode([
                    'backed_up_users' => isset($adminUsers) ? $adminUsers->count() : 0,
                    'deleted_users' => $deletedCount ?? 0,
                    'timestamp' => now()->toISOString(),
                    'environment' => app()->environment(),
                ]),
                'user_agent' => 'Migration Script',
                'ip_address' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            Log::info('AdminReplacement: Admin user replacement migration completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminReplacement: Migration failed: ' . $e->getMessage());
            
            // Log the failure
            if (Schema::hasTable('admin_replacement_logs')) {
                DB::table('admin_replacement_logs')->insert([
                    'action' => 'admin_replacement_migration',
                    'status' => 'failed',
                    'details' => json_encode([
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'timestamp' => now()->toISOString(),
                    ]),
                    'user_agent' => 'Migration Script',
                    'ip_address' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!app()->environment('production')) {
            Log::info('AdminReplacement: Skipping rollback in ' . app()->environment() . ' environment');
            return;
        }

        Log::info('AdminReplacement: Starting admin user rollback process');

        DB::beginTransaction();

        try {
            // Restore admin users from backup
            if (Schema::hasTable('admin_users_backup')) {
                $backupUsers = DB::table('admin_users_backup')
                    ->where('backup_reason', 'admin_replacement_migration')
                    ->get();

                $restoredCount = 0;
                foreach ($backupUsers as $backupUser) {
                    $userData = json_decode($backupUser->user_data, true);
                    
                    // Restore the user (force create even if soft deleted exists)
                    User::withTrashed()
                        ->where('id', $backupUser->original_user_id)
                        ->forceDelete(); // Completely remove if exists
                    
                    $restoredUser = User::create([
                        'id' => $backupUser->original_user_id,
                        'name' => $backupUser->name,
                        'email' => $backupUser->email,
                        'username' => $backupUser->username,
                        'password' => $backupUser->password,
                        'role_id' => Role::where('name', 'admin')->first()?->id,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ] + $userData);

                    // Assign admin role via Spatie if exists
                    if ($restoredUser && method_exists($restoredUser, 'assignRole')) {
                        $restoredUser->assignRole('admin');
                    }

                    $restoredCount++;
                    Log::info('AdminReplacement: Restored admin user: ' . $backupUser->email);
                }

                // Log the rollback
                if (Schema::hasTable('admin_replacement_logs')) {
                    DB::table('admin_replacement_logs')->insert([
                        'action' => 'admin_users_rollback',
                        'status' => 'success',
                        'details' => json_encode([
                            'restored_users' => $restoredCount,
                            'timestamp' => now()->toISOString(),
                        ]),
                        'user_agent' => 'Migration Rollback',
                        'ip_address' => 'system',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info('AdminReplacement: Rollback completed, restored ' . $restoredCount . ' admin users');
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminReplacement: Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }
};