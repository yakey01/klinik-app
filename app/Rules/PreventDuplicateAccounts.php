<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

class PreventDuplicateAccounts implements ValidationRule
{
    protected $currentUserId;
    
    public function __construct($currentUserId = null)
    {
        $this->currentUserId = $currentUserId;
    }
    
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Let required rule handle empty values
        }
        
        // Get role ID and name from form data
        $roleId = request()->get('role_id');
        $name = request()->get('name');
        
        if (!$roleId || !$name) {
            return; // Skip validation if essential data is missing
        }
        
        $role = \App\Models\Role::find($roleId);
        if (!$role) {
            return;
        }
        
        // Check for potential duplicate accounts based on email and name similarity
        $query = User::where('email', '!=', $value); // Different email
        
        // Exclude current user if updating
        if ($this->currentUserId) {
            $query->where('id', '!=', $this->currentUserId);
        }
        
        $existingUsers = $query->get();
        
        foreach ($existingUsers as $user) {
            // Check for very similar names (indicating potential duplicate person)
            $similarity = $this->calculateNameSimilarity($name, $user->name);
            
            if ($similarity > 0.8) { // 80% similarity threshold
                // If same role, definitely a concern
                if ($user->role_id == $roleId) {
                    $fail("Kemungkinan akun duplikat terdeteksi. Sudah ada user '{$user->name}' dengan role {$role->display_name}. Periksa kembali data yang diinput.");
                    return;
                }
                
                // If different role but critical roles, still flag it
                $criticalRoles = ['petugas', 'bendahara', 'pegawai'];
                if (in_array($role->name, $criticalRoles) && 
                    $user->role && in_array($user->role->name, $criticalRoles)) {
                    $fail("Peringatan: Nama '{$name}' sangat mirip dengan user existing '{$user->name}' (role: {$user->role->display_name}). Pastikan ini bukan akun duplikat untuk orang yang sama.");
                    return;
                }
            }
        }
        
        // Additional check: same person with different email patterns
        $emailPrefix = explode('@', $value)[0];
        $nameWords = explode(' ', strtolower($name));
        
        // Check if email prefix matches name pattern
        foreach ($nameWords as $word) {
            if (strlen($word) > 3 && stripos($emailPrefix, $word) !== false) {
                $similarEmailQuery = User::where('email', 'like', "%{$word}%")
                    ->where('email', '!=', $value);
                    
                if ($this->currentUserId) {
                    $similarEmailQuery->where('id', '!=', $this->currentUserId);
                }
                
                $similarEmailUser = $similarEmailQuery->first();
                
                if ($similarEmailUser) {
                    $nameSimilarity = $this->calculateNameSimilarity($name, $similarEmailUser->name);
                    if ($nameSimilarity > 0.7) {
                        $fail("Kemungkinan akun duplikat terdeteksi berdasarkan pola email dan nama. User '{$similarEmailUser->name}' dengan email '{$similarEmailUser->email}' sudah ada. Periksa kembali data yang diinput.");
                        return;
                    }
                }
            }
        }
    }
    
    /**
     * Calculate name similarity using Levenshtein distance
     */
    private function calculateNameSimilarity($name1, $name2): float
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        if ($name1 === $name2) {
            return 1.0;
        }
        
        $maxLength = max(strlen($name1), strlen($name2));
        if ($maxLength === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($name1, $name2);
        return 1 - ($distance / $maxLength);
    }
}