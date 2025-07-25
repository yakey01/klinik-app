<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VerifikatorRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create verifikator role
        $verifikatorRole = Role::firstOrCreate(
            ['name' => 'verifikator'],
            [
                'display_name' => 'Verifikator',
                'description' => 'Role untuk verifikator data pasien',
                'is_active' => true,
            ]
        );

        // Create permissions for verifikator
        $permissions = [
            'view_pasien_verification',
            'verify_pasien',
            'reject_pasien',
            'reset_pasien_status',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to verifikator role
        $verifikatorRole->givePermissionTo($permissions);

        echo "Verifikator role and permissions created successfully.\n";
    }
}