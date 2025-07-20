<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;

trait RoleSetupTrait
{
    /**
     * Track if roles have been setup to prevent repeated calls
     */
    private static $rolesSetup = false;

    /**
     * Reset role setup tracking (call this when database is refreshed)
     */
    protected function resetRoleSetup(): void
    {
        self::$rolesSetup = false;
    }

    /**
     * Setup roles for testing with atomic operations
     */
    protected function setupRoles(): void
    {
        // Skip if roles table doesn't exist yet (migrations haven't run)
        if (!\Schema::hasTable('roles')) {
            return;
        }

        // Skip if already setup in this test session
        if (self::$rolesSetup) {
            return;
        }

        // Skip if roles already exist in database
        if (Role::count() > 0) {
            self::$rolesSetup = true;
            return;
        }

        // Use database transaction for atomic role creation
        try {
            \DB::transaction(function () {
                $roles = [
                    ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'System Administrator'],
                    ['name' => 'dokter', 'display_name' => 'Dokter', 'description' => 'Medical Doctor'],
                    ['name' => 'paramedis', 'display_name' => 'Paramedis', 'description' => 'Medical Paramedic'],
                    ['name' => 'non_paramedis', 'display_name' => 'Non Paramedis', 'description' => 'Non Medical Staff'],
                    ['name' => 'petugas', 'display_name' => 'Petugas', 'description' => 'Staff Petugas'],
                    ['name' => 'bendahara', 'display_name' => 'Bendahara', 'description' => 'Staff Bendahara'],
                    ['name' => 'manajer', 'display_name' => 'Manajer', 'description' => 'Manager'],
                    ['name' => 'supervisor', 'display_name' => 'Supervisor', 'description' => 'Supervisor'],
                    ['name' => 'manager', 'display_name' => 'Manager', 'description' => 'Manager'], // Alternative spelling
                    ['name' => 'guest', 'display_name' => 'Guest', 'description' => 'Unauthorized User'],
                ];

                foreach ($roles as $role) {
                    try {
                        Role::firstOrCreate(
                            ['name' => $role['name']],
                            [
                                'display_name' => $role['display_name'],
                                'description' => $role['description'] ?? null,
                                'guard_name' => 'web'
                            ]
                        );
                    } catch (\Exception $e) {
                        // Role already exists or other database error, continue
                        if (!str_contains($e->getMessage(), 'UNIQUE constraint failed') && 
                            !str_contains($e->getMessage(), 'already exists')) {
                            throw $e;
                        }
                    }
                }
            });
            
            // Mark as setup after successful creation
            self::$rolesSetup = true;
        } catch (\Exception $e) {
            // If transaction fails completely, log and continue
            // This prevents test failures due to database setup issues
            error_log("Role setup failed: " . $e->getMessage());
        }
    }

    /**
     * Get role by name
     */
    protected function getRole(string $name): Role
    {
        // Ensure roles are setup before getting
        $this->setupRoles();
        
        $role = Role::where('name', $name)->first();
        
        if (!$role) {
            throw new \Exception("Role '{$name}' not found. Available roles: " . Role::pluck('name')->implode(', '));
        }
        
        return $role;
    }

    /**
     * Create user with specific role
     */
    protected function createUserWithRole(string $roleName, array $userData = []): \App\Models\User
    {
        $role = $this->getRole($roleName);
        
        $user = \App\Models\User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test.' . $roleName . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ], $userData));

        $user->assignRole($role);
        
        return $user;
    }
} 