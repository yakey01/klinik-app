<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;

trait RoleSetupTrait
{
    /**
     * Setup roles for testing with atomic operations
     */
    protected function setupRoles(): void
    {
        // Use database transaction for atomic role creation
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
                    // Role already exists, continue
                    if (!str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                        throw $e;
                    }
                }
            }
        });
    }

    /**
     * Get role by name
     */
    protected function getRole(string $name): Role
    {
        return Role::where('name', $name)->firstOrFail();
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