<?php

use App\Models\User;
use App\Models\Role;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\DB;

try {
    echo "=== DATABASE INTEGRITY CHECK ===" . PHP_EOL;
    
    // Check users table constraints
    echo "Checking users table..." . PHP_EOL;
    $usersWithNullEmail = User::whereNull('email')->count();
    echo "Users with NULL email: {$usersWithNullEmail}" . PHP_EOL;
    
    $usersWithEmptyEmail = User::where('email', '')->count();
    echo "Users with empty email: {$usersWithEmptyEmail}" . PHP_EOL;
    
    // Check duplicate emails
    $duplicateEmails = DB::table('users')
        ->select('email', DB::raw('COUNT(*) as count'))
        ->groupBy('email')
        ->having('count', '>', 1)
        ->get();
        
    if ($duplicateEmails->count() > 0) {
        echo "Duplicate emails found:" . PHP_EOL;
        foreach ($duplicateEmails as $duplicate) {
            echo "  - {$duplicate->email} ({$duplicate->count} times)" . PHP_EOL;
        }
    } else {
        echo "No duplicate emails found ✅" . PHP_EOL;
    }
    
    // Check table structure
    echo PHP_EOL . "Users table info:" . PHP_EOL;
    $tableInfo = DB::select("PRAGMA table_info(users)");
    foreach ($tableInfo as $column) {
        echo "  - {$column->name}: {$column->type} " . ($column->notnull ? '(NOT NULL)' : '') . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Database integrity check complete\!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error during integrity check: " . $e->getMessage() . PHP_EOL;
}
