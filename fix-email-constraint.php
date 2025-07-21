<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;

try {
    echo "=== CHECKING FOR PROBLEMATIC USER UPDATES ===" . PHP_EOL;
    
    // Check if there are any users with problematic data
    $users = User::all();
    
    foreach ($users as $user) {
        if (empty($user->email)) {
            echo "Found user with empty email: ID {$user->id} - {$user->name}" . PHP_EOL;
            
            // Fix by generating a default email
            $defaultEmail = 'user' . $user->id . '@dokterkuklinik.com';
            $user->email = $defaultEmail;
            $user->save();
            
            echo "Fixed: Set email to {$defaultEmail}" . PHP_EOL;
        }
    }
    
    echo "=== CONSTRAINT CHECK COMPLETE ===" . PHP_EOL;
    echo "Total users checked: " . $users->count() . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
