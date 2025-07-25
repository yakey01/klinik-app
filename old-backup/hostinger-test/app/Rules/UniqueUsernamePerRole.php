<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use App\Models\Role;

class UniqueUsernamePerRole implements ValidationRule
{
    protected $roleId;
    protected $currentUserId;
    
    public function __construct($roleId = null, $currentUserId = null)
    {
        $this->roleId = $roleId;
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
        
        // Get role ID from form data if not provided in constructor
        $roleId = $this->roleId ?? request()->get('role_id');
        
        if (!$roleId) {
            $fail('Role harus dipilih terlebih dahulu.');
            return;
        }
        
        // Get role information
        $role = Role::find($roleId);
        if (!$role) {
            $fail('Role tidak valid.');
            return;
        }
        
        // Check if username already exists for this role
        $query = User::where('username', $value)
                    ->where('role_id', $roleId);
                    
        // Exclude current user if updating
        if ($this->currentUserId) {
            $query->where('id', '!=', $this->currentUserId);
        }
        
        $existingUser = $query->first();
        
        if ($existingUser) {
            $fail("Username '{$value}' sudah digunakan oleh {$role->display_name} lain. Username harus unik untuk setiap role.");
            return;
        }
        
        // Additional check: prevent same username across different critical roles
        $criticalRoles = ['petugas', 'bendahara', 'paramedis'];
        $currentRole = $role->name;
        
        if (in_array($currentRole, $criticalRoles)) {
            $otherCriticalRoles = array_diff($criticalRoles, [$currentRole]);
            $roleIds = Role::whereIn('name', $otherCriticalRoles)->pluck('id');
            
            $duplicateQuery = User::where('username', $value)
                                 ->whereIn('role_id', $roleIds);
                                 
            // Exclude current user if updating
            if ($this->currentUserId) {
                $duplicateQuery->where('id', '!=', $this->currentUserId);
            }
            
            $duplicateUser = $duplicateQuery->with('role')->first();
            
            if ($duplicateUser) {
                $fail("Username '{$value}' sudah digunakan oleh {$duplicateUser->role->display_name}. Username tidak boleh sama antara Petugas, Bendahara, dan Paramedis.");
                return;
            }
        }
    }
}