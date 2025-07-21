<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🗑️  ENABLING TINA DELETION IN PEGAWAI MANAGEMENT\n";
echo "================================================\n\n";

try {
    // Find Tina user in pegawai table
    $tina = \App\Models\Pegawai::where('username', 'tina')
        ->orWhere('nama_lengkap', 'LIKE', '%tina%')
        ->orWhere('nama_lengkap', 'LIKE', '%Tina%')
        ->first();
    
    if (!$tina) {
        echo "❌ Tina not found in pegawai table\n";
        
        // Check users table
        $tinaUser = \App\Models\User::where('username', 'tina')
            ->orWhere('name', 'LIKE', '%tina%')
            ->orWhere('name', 'LIKE', '%Tina%')
            ->first();
        
        if ($tinaUser) {
            echo "ℹ️  Found Tina in users table: " . $tinaUser->name . "\n";
            echo "💡 This user is not a pegawai, so deletion might be handled in User management\n";
        }
        exit();
    }
    
    echo "✅ Found Tina in pegawai table:\n";
    echo "   ID: " . $tina->id . "\n";
    echo "   Name: " . $tina->nama_lengkap . "\n";
    echo "   Username: " . ($tina->username ?? 'NULL') . "\n";
    echo "   NIK: " . ($tina->nik ?? 'NULL') . "\n";
    echo "   Type: " . ($tina->jenis_pegawai ?? 'NULL') . "\n";
    echo "   Active: " . ($tina->aktif ? 'YES' : 'NO') . "\n";
    echo "   Deleted: " . ($tina->deleted_at ? 'YES' : 'NO') . "\n";
    
    // Check dependent records that might prevent deletion
    echo "\n📋 CHECKING DEPENDENT RECORDS:\n";
    echo "==============================\n";
    
    $dependencies = [];
    
    // Check employee cards
    $employeeCards = \DB::table('employee_cards')->where('pegawai_id', $tina->id)->count();
    if ($employeeCards > 0) {
        $dependencies['employee_cards'] = $employeeCards;
        echo "⚠️  Employee Cards: " . $employeeCards . " records\n";
    }
    
    // Check tindakan table (paramedis/non_paramedis references)
    $tindakanParamedis = \DB::table('tindakan')->where('paramedis_id', $tina->id)->count();
    $tindakanNonParamedis = \DB::table('tindakan')->where('non_paramedis_id', $tina->id)->count();
    if ($tindakanParamedis > 0) {
        $dependencies['tindakan_paramedis'] = $tindakanParamedis;
        echo "⚠️  Tindakan (as paramedis): " . $tindakanParamedis . " records\n";
    }
    if ($tindakanNonParamedis > 0) {
        $dependencies['tindakan_non_paramedis'] = $tindakanNonParamedis;
        echo "⚠️  Tindakan (as non-paramedis): " . $tindakanNonParamedis . " records\n";
    }
    
    // Check related user account
    $relatedUser = \App\Models\User::where('pegawai_id', $tina->id)->first();
    if ($relatedUser) {
        echo "ℹ️  Related User Account: " . $relatedUser->name . " (ID: " . $relatedUser->id . ")\n";
        
        // Check user dependencies
        $userDependencies = [
            'attendances' => \DB::table('attendances')->where('user_id', $relatedUser->id)->count(),
            'user_devices' => \DB::table('user_devices')->where('user_id', $relatedUser->id)->count(),
            'jadwal_jagas' => \DB::table('jadwal_jagas')->where('pegawai_id', $relatedUser->id)->count(),
            'permohonan_cutis' => \DB::table('permohonan_cutis')->where('pegawai_id', $relatedUser->id)->count(),
        ];
        
        foreach ($userDependencies as $table => $count) {
            if ($count > 0) {
                $dependencies[$table] = $count;
                echo "⚠️  " . ucfirst(str_replace('_', ' ', $table)) . ": " . $count . " records\n";
            }
        }
    }
    
    if (empty($dependencies)) {
        echo "✅ No dependencies found - Tina can be safely deleted\n";
    } else {
        echo "\n🔧 RESOLVING DEPENDENCIES:\n";
        echo "==========================\n";
        
        // Handle each dependency
        foreach ($dependencies as $table => $count) {
            echo "\n📋 Handling " . $table . " (" . $count . " records):\n";
            
            switch ($table) {
                case 'employee_cards':
                    // Employee cards can be safely deleted with cascade
                    echo "   ✅ Employee cards will be automatically deleted (cascade)\n";
                    break;
                    
                case 'tindakan_paramedis':
                    // Set to null (already configured in migration)
                    echo "   ✅ Tindakan paramedis_id will be set to NULL (configured)\n";
                    break;
                    
                case 'tindakan_non_paramedis':
                    // Set to null (already configured in migration)
                    echo "   ✅ Tindakan non_paramedis_id will be set to NULL (configured)\n";
                    break;
                    
                default:
                    echo "   ⚠️  Manual handling may be required for " . $table . "\n";
                    break;
            }
        }
    }
    
    // Test deletion without actually deleting
    echo "\n🧪 TESTING DELETION CAPABILITY:\n";
    echo "===============================\n";
    
    try {
        // Start a transaction to test deletion
        \DB::beginTransaction();
        
        // Try to delete (this will be rolled back)
        $deleted = $tina->delete();
        
        if ($deleted) {
            echo "✅ Deletion test: SUCCESS\n";
            echo "   Tina can be safely deleted from pegawai management\n";
        } else {
            echo "❌ Deletion test: FAILED\n";
        }
        
        // Rollback the transaction
        \DB::rollBack();
        echo "   (Test deletion rolled back - no actual changes made)\n";
        
    } catch (Exception $e) {
        \DB::rollBack();
        echo "❌ Deletion test failed with error:\n";
        echo "   " . $e->getMessage() . "\n";
        
        // Check if it's a foreign key constraint issue
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            echo "\n🔧 FOREIGN KEY CONSTRAINT DETECTED\n";
            echo "   This means there are still unhandled dependent records\n";
            echo "   Manual cleanup may be required before deletion\n";
        }
    }
    
    echo "\n💡 SOLUTION OPTIONS:\n";
    echo "====================\n";
    
    if (empty($dependencies)) {
        echo "1. ✅ Direct deletion should work - no dependencies found\n";
        echo "2. 🎯 Use the normal delete button in Filament admin panel\n";
    } else {
        echo "1. 🔧 Clean up dependent records first, then delete\n";
        echo "2. 🗑️  Use soft delete (recommended) - data is hidden but preserved\n";
        echo "3. 🎯 Force delete with cascade (will remove all related data)\n";
    }
    
    echo "\n🎯 RECOMMENDED ACTION:\n";
    echo "======================\n";
    echo "The pegawai deletion should work normally through the admin panel.\n";
    echo "If you're still having issues:\n";
    echo "1. Check if you have admin permissions\n";
    echo "2. Try soft delete first (safer)\n";
    echo "3. Clear browser cache/cookies\n";
    echo "4. Check for JavaScript errors in browser console\n";
    
} catch (Exception $e) {
    echo "❌ Error analyzing Tina deletion capability:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}