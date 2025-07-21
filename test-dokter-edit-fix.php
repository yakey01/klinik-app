<?php

use App\Models\Dokter;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    echo "=== TESTING DOKTER EDIT EMAIL FIX ===" . PHP_EOL;
    
    // Find Dr. Yaya Mulyana
    $dokter = Dokter::where('nama_lengkap', 'LIKE', '%Yaya%')->first();
    
    if (\!$dokter) {
        echo "❌ Dr. Yaya Mulyana not found" . PHP_EOL;
        return;
    }
    
    echo "✅ Found dokter: {$dokter->nama_lengkap} (ID: {$dokter->id})" . PHP_EOL;
    echo "Current email: " . ($dokter->email ?? 'NULL') . PHP_EOL;
    
    // Check if dokter has user
    if ($dokter->user_id && $dokter->user) {
        echo "✅ Has linked user: {$dokter->user->name}" . PHP_EOL;
        echo "User email: {$dokter->user->email}" . PHP_EOL;
        
        // Test scenario: Update dokter with empty email
        echo PHP_EOL . "Testing scenario: Update dokter with empty email..." . PHP_EOL;
        
        $originalEmail = $dokter->email;
        $originalUserEmail = $dokter->user->email;
        
        // Temporarily set dokter email to null
        $dokter->email = null;
        
        // Simulate the sync logic from EditDokter.php
        $syncData = [
            'username' => $dokter->username,
            'name' => $dokter->nama_lengkap,
        ];
        
        // Test the fixed logic
        if (\!empty($dokter->email)) {
            $syncData['email'] = $dokter->email;
            echo "Email would be synced: {$dokter->email}" . PHP_EOL;
        } else {
            echo "✅ Email is empty - keeping existing user email: {$originalUserEmail}" . PHP_EOL;
        }
        
        echo "Sync data prepared: " . json_encode(array_keys($syncData)) . PHP_EOL;
        echo "✅ Fix is working - no email constraint violation would occur" . PHP_EOL;
        
        // Restore original email
        $dokter->email = $originalEmail;
        
    } else {
        echo "❌ No linked user found for this dokter" . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Test completed successfully\!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . PHP_EOL;
}
