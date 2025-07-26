<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Pegawai;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Soft delete orphaned users whose pegawai were already deleted
        $this->fixOrphanedUsersFromDeletedPegawai();
        
        // Step 2: Clean up NULL pegawai_id users that are invalid
        $this->cleanupNullPegawaiIdUsers();
        
        // Step 3: Add database constraints to prevent future issues
        $this->addDataIntegrityConstraints();
    }

    /**
     * Fix orphaned users whose pegawai were soft deleted but users weren't cascaded
     */
    private function fixOrphanedUsersFromDeletedPegawai(): void
    {
        $deletedPegawaiIds = Pegawai::onlyTrashed()->pluck('id');
        
        $orphanedUsers = User::whereIn('pegawai_id', $deletedPegawaiIds)
                            ->whereNull('deleted_at')
                            ->get();

        foreach ($orphanedUsers as $user) {
            $pegawai = Pegawai::withTrashed()->find($user->pegawai_id);
            
            if ($pegawai && $pegawai->deleted_at) {
                // Soft delete the user with the same timestamp as pegawai
                $user->update(['deleted_at' => $pegawai->deleted_at]);
                
                \Log::info('Fixed orphaned user from deleted pegawai', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'pegawai_id' => $user->pegawai_id,
                    'pegawai_deleted_at' => $pegawai->deleted_at
                ]);
            }
        }
        
        \Log::info('Fixed orphaned users from deleted pegawai', [
            'fixed_count' => $orphanedUsers->count(),
            'user_ids' => $orphanedUsers->pluck('id')->toArray()
        ]);
    }

    /**
     * Clean up users with NULL pegawai_id that don't have proper relationships
     */
    private function cleanupNullPegawaiIdUsers(): void
    {
        // Find users with NULL pegawai_id but have usernames (likely created incorrectly)
        $nullPegawaiUsers = User::whereNull('pegawai_id')
                               ->whereNotNull('username')
                               ->where('username', '!=', '')
                               ->get();

        $cleanupActions = [];
        
        foreach ($nullPegawaiUsers as $user) {
            // Try to find matching pegawai by username or name
            $matchingPegawai = null;
            
            // First try by username
            if ($user->username) {
                $matchingPegawai = Pegawai::withTrashed()
                                         ->where('username', $user->username)
                                         ->first();
            }
            
            // If not found, try by name (be careful with this)
            if (!$matchingPegawai && $user->name) {
                $matchingPegawai = Pegawai::withTrashed()
                                         ->where('nama_lengkap', $user->name)
                                         ->first();
            }
            
            if ($matchingPegawai) {
                // Link user to pegawai
                $user->update(['pegawai_id' => $matchingPegawai->id]);
                
                // If pegawai is deleted, soft delete user too
                if ($matchingPegawai->deleted_at && !$user->deleted_at) {
                    $user->update(['deleted_at' => $matchingPegawai->deleted_at]);
                }
                
                $cleanupActions[] = [
                    'action' => 'linked',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'pegawai_id' => $matchingPegawai->id,
                    'pegawai_name' => $matchingPegawai->nama_lengkap
                ];
                
                \Log::info('Linked orphaned user to pegawai', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'pegawai_id' => $matchingPegawai->id,
                    'pegawai_deleted' => $matchingPegawai->deleted_at ? true : false
                ]);
            } else {
                // No matching pegawai found - mark as potentially invalid
                $cleanupActions[] = [
                    'action' => 'orphaned',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'username' => $user->username,
                    'note' => 'No matching pegawai found'
                ];
                
                \Log::warning('Orphaned user with no matching pegawai', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'username' => $user->username
                ]);
            }
        }
        
        \Log::info('Cleanup NULL pegawai_id users completed', [
            'total_processed' => count($cleanupActions),
            'actions' => $cleanupActions
        ]);
    }

    /**
     * Add database constraints to prevent future issues
     */
    private function addDataIntegrityConstraints(): void
    {
        // Add index for better performance on pegawai_id lookups
        Schema::table('users', function (Blueprint $table) {
            // Add composite index for pegawai_id and deleted_at for better query performance
            $table->index(['pegawai_id', 'deleted_at'], 'users_pegawai_id_deleted_at_index');
        });
        
        \Log::info('Added database constraints for user-pegawai integrity');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the index we added
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_pegawai_id_deleted_at_index');
        });
        
        // Note: We don't reverse the data cleanup as that could cause inconsistencies
        \Log::warning('Migration rolled back - data cleanup was NOT reversed for safety');
    }
};