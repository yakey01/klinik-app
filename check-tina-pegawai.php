<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 COMPREHENSIVE TINA SEARCH IN PEGAWAI MANAGEMENT\n";
echo "=================================================\n\n";

try {
    // Search for Tina in pegawais table with various patterns
    echo "📋 SEARCHING FOR TINA IN PEGAWAI TABLE:\n";
    echo "=======================================\n";
    
    $searchPatterns = [
        'tina',
        'Tina',
        'TINA',
        '%tina%',
        '%Tina%',
        '%TINA%'
    ];
    
    $foundPegawai = false;
    
    foreach ($searchPatterns as $pattern) {
        $pegawais = \App\Models\Pegawai::withTrashed()
            ->where(function($query) use ($pattern) {
                $query->where('nama_lengkap', 'LIKE', $pattern)
                      ->orWhere('username', 'LIKE', $pattern)
                      ->orWhere('nik', 'LIKE', $pattern);
            })
            ->get();
        
        if ($pegawais->count() > 0) {
            echo "✅ Found " . $pegawais->count() . " pegawai record(s) matching '$pattern':\n";
            
            foreach ($pegawais as $pegawai) {
                $foundPegawai = true;
                echo "   📋 Pegawai ID: " . $pegawai->id . "\n";
                echo "      Name: " . $pegawai->nama_lengkap . "\n";
                echo "      Username: " . ($pegawai->username ?? 'NULL') . "\n";
                echo "      NIK: " . ($pegawai->nik ?? 'NULL') . "\n";
                echo "      Type: " . ($pegawai->jenis_pegawai ?? 'NULL') . "\n";
                echo "      Active: " . ($pegawai->aktif ? 'YES' : 'NO') . "\n";
                echo "      Deleted: " . ($pegawai->deleted_at ? 'YES (Soft Deleted)' : 'NO') . "\n";
                echo "      Created: " . $pegawai->created_at . "\n";
                echo "\n";
            }
        }
    }
    
    if (!$foundPegawai) {
        echo "❌ No pegawai records found for 'Tina'\n\n";
        
        // Check if there are any pegawai records at all
        $totalPegawai = \App\Models\Pegawai::withTrashed()->count();
        echo "📊 Total pegawai records in database: " . $totalPegawai . "\n";
        
        if ($totalPegawai > 0) {
            echo "\n📋 Sample pegawai records (first 5):\n";
            $samples = \App\Models\Pegawai::withTrashed()->take(5)->get();
            foreach ($samples as $sample) {
                echo "   - " . $sample->nama_lengkap . " (Username: " . ($sample->username ?? 'NULL') . ")\n";
            }
        }
    }
    
    // Check users table for reference
    echo "\n📋 CHECKING USERS TABLE FOR TINA:\n";
    echo "=================================\n";
    
    $tinaUsers = \App\Models\User::withTrashed()
        ->where(function($query) {
            $query->where('name', 'LIKE', '%tina%')
                  ->orWhere('name', 'LIKE', '%Tina%')
                  ->orWhere('username', 'LIKE', '%tina%')
                  ->orWhere('username', 'LIKE', '%Tina%');
        })
        ->get();
    
    if ($tinaUsers->count() > 0) {
        echo "✅ Found " . $tinaUsers->count() . " user record(s) for Tina:\n";
        
        foreach ($tinaUsers as $user) {
            echo "   👤 User ID: " . $user->id . "\n";
            echo "      Name: " . $user->name . "\n";
            echo "      Username: " . ($user->username ?? 'NULL') . "\n";
            echo "      Email: " . ($user->email ?? 'NULL') . "\n";
            echo "      Role: " . ($user->role?->name ?? 'NULL') . "\n";
            echo "      Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
            echo "      Deleted: " . ($user->deleted_at ? 'YES (Soft Deleted)' : 'NO') . "\n";
            echo "      Pegawai ID: " . ($user->pegawai_id ?? 'NULL') . "\n";
            echo "\n";
            
            // Check if this user has a corresponding pegawai record
            if ($user->pegawai_id) {
                $relatedPegawai = \App\Models\Pegawai::withTrashed()->find($user->pegawai_id);
                if ($relatedPegawai) {
                    echo "      🔗 Related Pegawai: " . $relatedPegawai->nama_lengkap . "\n";
                } else {
                    echo "      ⚠️  Related Pegawai ID " . $user->pegawai_id . " not found\n";
                }
            }
        }
    } else {
        echo "❌ No user records found for 'Tina'\n";
    }
    
    // Solution based on findings
    echo "\n🎯 SOLUTION FOR TINA DELETION:\n";
    echo "==============================\n";
    
    if ($foundPegawai) {
        echo "✅ PEGAWAI FOUND: Tina exists in Pegawai Management\n";
        echo "📍 Location: /admin/pegawais\n";
        echo "🗑️  Delete action should be available in the table row\n";
        echo "\n🔧 If delete button is not visible:\n";
        echo "   1. Check admin permissions\n";
        echo "   2. Look for soft-deleted records\n";
        echo "   3. Check for dependent records preventing deletion\n";
    } else {
        echo "❌ PEGAWAI NOT FOUND: Tina does not exist in Pegawai Management\n";
        
        if ($tinaUsers->count() > 0) {
            echo "💡 ALTERNATIVE SOLUTION:\n";
            echo "   Tina exists as a USER, not a PEGAWAI\n";
            echo "   📍 Go to: /admin/users instead\n";
            echo "   🗑️  Delete from User Management\n";
            echo "\n🆕 TO CREATE PEGAWAI FOR TINA:\n";
            echo "   1. Go to /admin/pegawais\n";
            echo "   2. Click 'Create' button\n";
            echo "   3. Fill in Tina's information\n";
            echo "   4. Save the pegawai record\n";
            echo "   5. Then you can delete from Pegawai Management\n";
        } else {
            echo "❌ Tina not found anywhere in the system\n";
            echo "   She may have already been deleted\n";
        }
    }
    
    // Check if pegawai deletion is enabled
    echo "\n🔧 CHECKING PEGAWAI DELETION CAPABILITY:\n";
    echo "=======================================\n";
    
    // Check PegawaiResource for delete restrictions
    $pegawaiResourceFile = app_path('Filament/Resources/PegawaiResource.php');
    if (file_exists($pegawaiResourceFile)) {
        $content = file_get_contents($pegawaiResourceFile);
        
        if (strpos($content, 'DeleteAction::make()') !== false) {
            echo "✅ Delete action is configured in PegawaiResource\n";
        } else {
            echo "❌ Delete action not found in PegawaiResource\n";
        }
        
        if (strpos($content, 'canDelete') !== false) {
            echo "⚠️  Custom delete permissions may be configured\n";
        } else {
            echo "✅ No custom delete restrictions found\n";
        }
    }
    
    echo "\n📋 FINAL RECOMMENDATION:\n";
    echo "========================\n";
    
    if ($foundPegawai) {
        $activePegawai = \App\Models\Pegawai::where('nama_lengkap', 'LIKE', '%tina%')
            ->orWhere('nama_lengkap', 'LIKE', '%Tina%')
            ->whereNull('deleted_at')
            ->first();
        
        if ($activePegawai) {
            echo "🎯 DIRECT SOLUTION: Go to /admin/pegawais and delete Tina\n";
            echo "   If delete button not visible, clear browser cache\n";
        } else {
            echo "⚠️  Tina pegawai record is soft-deleted\n";
            echo "   Check 'Include deleted records' filter\n";
        }
    } else {
        echo "🎯 TINA IS NOT A PEGAWAI\n";
        echo "   She exists only as a User\n";
        echo "   Use User Management (/admin/users) to delete her\n";
        echo "\n   OR create a Pegawai record for her first\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error searching for Tina:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}