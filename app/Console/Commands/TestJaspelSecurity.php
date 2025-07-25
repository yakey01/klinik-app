<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Jaspel;
use App\Services\EnhancedJaspelService;

class TestJaspelSecurity extends Command
{
    protected $signature = 'test:jaspel-security';
    
    protected $description = 'Test Jaspel data security to ensure no data leakage between users';

    public function handle()
    {
        $this->info('🔒 JASPEL SECURITY TEST');
        $this->info('======================');
        $this->newLine();

        try {
            // Test 1: Check users with Jaspel data
            $usersWithJaspel = User::whereHas('jaspel')->get();
            $this->info("1️⃣ Users with Jaspel data: " . $usersWithJaspel->count());
            
            if ($usersWithJaspel->count() < 2) {
                $this->warn("   ⚠️  Need at least 2 users with Jaspel data to test properly");
                return 1;
            }
            
            // Test 2: Check data segregation at database level
            $this->newLine();
            $this->info("2️⃣ Data Segregation Test:");
            foreach ($usersWithJaspel->take(3) as $user) {
                $userJaspelCount = Jaspel::where('user_id', $user->id)->count();
                $this->info("   👤 {$user->name} (ID: {$user->id}): {$userJaspelCount} Jaspel records");
            }
            
            // Test 3: Service-level security test
            $this->newLine();
            $this->info("3️⃣ Service Security Test:");
            $enhancedService = new EnhancedJaspelService();
            
            $testUser1 = $usersWithJaspel->first();
            $testUser2 = $usersWithJaspel->skip(1)->first();
            
            $this->info("   Testing with User 1: {$testUser1->name} (ID: {$testUser1->id})");
            $user1Data = $enhancedService->getComprehensiveJaspelData($testUser1);
            $this->info("   User 1 got " . count($user1Data['jaspel_items']) . " records");
            
            $this->info("   Testing with User 2: {$testUser2->name} (ID: {$testUser2->id})");
            $user2Data = $enhancedService->getComprehensiveJaspelData($testUser2);
            $this->info("   User 2 got " . count($user2Data['jaspel_items']) . " records");
            
            // Test 4: Cross-contamination check
            $this->newLine();
            $this->info("4️⃣ Cross-Contamination Check:");
            
            $user1Items = collect($user1Data['jaspel_items']);
            $user2Items = collect($user2Data['jaspel_items']);
            
            // Check if any record from user1's data belongs to user2
            $contaminatedRecords = [];
            foreach ($user1Items as $item) {
                // Check direct user_id field or extract from meta data
                $itemUserId = $item['user_id'] ?? ($item['meta']['user_id'] ?? null);
                if ($itemUserId && $itemUserId != $testUser1->id) {
                    $contaminatedRecords[] = $item;
                }
            }
            
            if (empty($contaminatedRecords)) {
                $this->info("   ✅ No cross-contamination detected in User 1's data");
            } else {
                $this->error("   ❌ SECURITY VIOLATION: User 1 received " . count($contaminatedRecords) . " records from other users!");
                foreach ($contaminatedRecords as $record) {
                    $recordId = $record['id'] ?? 'unknown';
                    $recordUserId = $record['user_id'] ?? ($record['meta']['user_id'] ?? 'unknown');
                    $this->error("      - Record ID: {$recordId} belongs to User ID: {$recordUserId}");
                }
            }
            
            // Same check for user2
            $contaminatedRecords2 = [];
            foreach ($user2Items as $item) {
                $itemUserId = $item['user_id'] ?? ($item['meta']['user_id'] ?? null);
                if ($itemUserId && $itemUserId != $testUser2->id) {
                    $contaminatedRecords2[] = $item;
                }
            }
            
            if (empty($contaminatedRecords2)) {
                $this->info("   ✅ No cross-contamination detected in User 2's data");
            } else {
                $this->error("   ❌ SECURITY VIOLATION: User 2 received " . count($contaminatedRecords2) . " records from other users!");
            }
            
            // Test 5: Database-level verification
            $this->newLine();
            $this->info("5️⃣ Database-Level Verification:");
            
            // Check if any Jaspel record has NULL user_id
            $nullUserRecords = Jaspel::whereNull('user_id')->count();
            if ($nullUserRecords > 0) {
                $this->warn("   ⚠️  Found {$nullUserRecords} Jaspel records with NULL user_id");
            } else {
                $this->info("   ✅ All Jaspel records have valid user_id");
            }
            
            // Check for orphaned records
            $orphanedRecords = Jaspel::whereNotExists(function($query) {
                $query->select('id')
                      ->from('users')
                      ->whereColumn('users.id', 'jaspel.user_id');
            })->count();
            
            if ($orphanedRecords > 0) {
                $this->warn("   ⚠️  Found {$orphanedRecords} Jaspel records with invalid user_id");
            } else {
                $this->info("   ✅ All Jaspel records have valid user references");
            }
            
            $this->newLine();
            $this->info("🔒 SECURITY TEST COMPLETED");
            $this->info("==========================");
            
            if (empty($contaminatedRecords) && empty($contaminatedRecords2) && $nullUserRecords == 0 && $orphanedRecords == 0) {
                $this->info("✅ ALL SECURITY CHECKS PASSED - No data leakage detected");
                return 0;
            } else {
                $this->error("❌ SECURITY VIOLATIONS DETECTED - Please review the issues above");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed with error: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}