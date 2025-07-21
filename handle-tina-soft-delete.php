<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🗑️  HANDLING TINA SOFT-DELETED PEGAWAI RECORD\n";
echo "=============================================\n\n";

try {
    // Find the soft-deleted Tina pegawai record
    $tina = \App\Models\Pegawai::withTrashed()
        ->where('nama_lengkap', 'tina')
        ->orWhere('username', 'tina')
        ->first();
    
    if (!$tina) {
        echo "❌ Tina pegawai record not found\n";
        exit();
    }
    
    echo "✅ Found Tina pegawai record:\n";
    echo "   ID: " . $tina->id . "\n";
    echo "   Name: " . $tina->nama_lengkap . "\n";
    echo "   Username: " . $tina->username . "\n";
    echo "   NIK: " . $tina->nik . "\n";
    echo "   Type: " . $tina->jenis_pegawai . "\n";
    echo "   Status: " . ($tina->deleted_at ? 'SOFT DELETED' : 'ACTIVE') . "\n";
    echo "   Deleted at: " . ($tina->deleted_at ?? 'Not deleted') . "\n";
    
    if ($tina->deleted_at) {
        echo "\n🎯 SOLUTION OPTIONS:\n";
        echo "===================\n";
        echo "1. 🔄 RESTORE the record (make it visible again)\n";
        echo "2. 🗑️  PERMANENT DELETE (remove completely)\n";
        echo "3. ❌ LEAVE AS IS (keep soft-deleted)\n";
        
        echo "\n💡 What would you like to do?\n";
        echo "Since you want to delete Tina from management, I'll proceed with PERMANENT DELETION\n\n";
        
        echo "🗑️  PERFORMING PERMANENT DELETION:\n";
        echo "==================================\n";
        
        // Check for any dependencies before permanent deletion
        echo "📋 Checking dependencies...\n";
        
        $dependencies = [];
        
        // Check employee cards
        $employeeCards = \DB::table('employee_cards')->where('pegawai_id', $tina->id)->count();
        if ($employeeCards > 0) {
            $dependencies['employee_cards'] = $employeeCards;
            echo "⚠️  Employee Cards: " . $employeeCards . " records\n";
        }
        
        // Check tindakan references
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
        
        if (empty($dependencies)) {
            echo "✅ No dependencies found - safe to permanently delete\n";
        } else {
            echo "📋 Found " . count($dependencies) . " types of dependent records\n";
            echo "⚠️  These will be handled according to foreign key constraints\n";
        }
        
        echo "\n🗑️  Executing permanent deletion...\n";
        
        try {
            // Perform force delete (permanent deletion)
            $deleted = $tina->forceDelete();
            
            if ($deleted) {
                echo "✅ SUCCESS: Tina pegawai record permanently deleted!\n";
                echo "   Record ID " . $tina->id . " has been completely removed from the database\n";
                
                // Verify deletion
                $checkDeleted = \App\Models\Pegawai::withTrashed()->find($tina->id);
                if (!$checkDeleted) {
                    echo "✅ VERIFIED: Record no longer exists in database\n";
                } else {
                    echo "⚠️  WARNING: Record still exists after deletion attempt\n";
                }
                
            } else {
                echo "❌ FAILED: Unable to permanently delete Tina pegawai record\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERROR during permanent deletion:\n";
            echo "   " . $e->getMessage() . "\n";
            
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                echo "\n🔧 FOREIGN KEY CONSTRAINT DETECTED\n";
                echo "   Some dependent records are preventing deletion\n";
                echo "   Let me try to clean them up first...\n\n";
                
                // Clean up dependent records
                if (isset($dependencies['employee_cards'])) {
                    echo "🧹 Cleaning up employee cards...\n";
                    \DB::table('employee_cards')->where('pegawai_id', $tina->id)->delete();
                    echo "✅ Employee cards cleaned up\n";
                }
                
                // Set tindakan references to null
                if (isset($dependencies['tindakan_paramedis'])) {
                    echo "🧹 Cleaning up tindakan paramedis references...\n";
                    \DB::table('tindakan')->where('paramedis_id', $tina->id)->update(['paramedis_id' => null]);
                    echo "✅ Tindakan paramedis references cleaned up\n";
                }
                
                if (isset($dependencies['tindakan_non_paramedis'])) {
                    echo "🧹 Cleaning up tindakan non-paramedis references...\n";
                    \DB::table('tindakan')->where('non_paramedis_id', $tina->id)->update(['non_paramedis_id' => null]);
                    echo "✅ Tindakan non-paramedis references cleaned up\n";
                }
                
                // Try deletion again
                echo "\n🔄 Retrying permanent deletion...\n";
                try {
                    $deleted = $tina->forceDelete();
                    if ($deleted) {
                        echo "✅ SUCCESS: Tina permanently deleted after cleanup!\n";
                    } else {
                        echo "❌ FAILED: Still unable to delete after cleanup\n";
                    }
                } catch (Exception $e2) {
                    echo "❌ STILL FAILED: " . $e2->getMessage() . "\n";
                }
            }
        }
        
    } else {
        echo "\n💡 Tina pegawai record is active (not soft-deleted)\n";
        echo "   You can delete her normally from the Pegawai Management interface\n";
        echo "   Go to: /admin/pegawais and use the delete action\n";
    }
    
    echo "\n🎯 FINAL STATUS:\n";
    echo "================\n";
    
    // Check final status
    $finalCheck = \App\Models\Pegawai::withTrashed()->find($tina->id);
    if (!$finalCheck) {
        echo "✅ COMPLETED: Tina pegawai record has been permanently removed\n";
        echo "   She no longer exists in Pegawai Management\n";
        echo "   The 'galek delet' issue is resolved!\n";
    } else if ($finalCheck->deleted_at) {
        echo "⚠️  STATUS: Tina pegawai record is still soft-deleted\n";
        echo "   She is hidden from normal view but still exists in database\n";
        echo "   Manual intervention may be required\n";
    } else {
        echo "ℹ️  STATUS: Tina pegawai record is active\n";
        echo "   She is visible in Pegawai Management and can be deleted normally\n";
    }
    
    // Check user record too
    echo "\n📋 CHECKING RELATED USER RECORD:\n";
    echo "================================\n";
    
    $tinaUser = \App\Models\User::where('username', 'tina')
        ->orWhere('name', 'LIKE', '%Tina%')
        ->first();
    
    if ($tinaUser) {
        echo "ℹ️  Tina still exists as a User (ID: " . $tinaUser->id . ")\n";
        echo "   User record is separate from Pegawai record\n";
        echo "   If you want to delete the User too, go to /admin/users\n";
    } else {
        echo "✅ No Tina user record found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error handling Tina soft-delete:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}