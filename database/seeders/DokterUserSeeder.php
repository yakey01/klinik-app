<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Dokter;
use Spatie\Permission\Models\Role as SpatieRole;

class DokterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏥 Creating DOKTER test users...');

        // Ensure roles exist
        $this->ensureRolesExist();

        // Create main dokter user
        $this->createDokterUser([
            'name' => 'Dr. Sarah Wijaya, Sp.PD',
            'email' => 'dokter@dokterku.com',
            'username' => 'dokter',
            'password' => Hash::make('password'),
            'nip' => 'DOK001',
            'no_telepon' => '081234567890',
            'tanggal_bergabung' => now()->subYears(2),
            'is_active' => true,
            'specialty' => 'Spesialis Penyakit Dalam',
            'license_number' => '503/SIP/2020',
        ]);

        // Create additional dokter users for testing
        $this->createDokterUser([
            'name' => 'Dr. Ahmad Santoso, Sp.JP',
            'email' => 'dokter.umum@dokterku.com',
            'username' => 'dokter_umum',
            'password' => Hash::make('password'),
            'nip' => 'DOK002',
            'no_telepon' => '081234567891',
            'tanggal_bergabung' => now()->subYears(1),
            'is_active' => true,
            'specialty' => 'Spesialis Jantung dan Pembuluh Darah',
            'license_number' => '504/SIP/2021',
        ]);

        $this->createDokterUser([
            'name' => 'Dr. Maya Putri, Sp.A',
            'email' => 'dokter.spesialis@dokterku.com',
            'username' => 'dokter_spesialis',
            'password' => Hash::make('password'),
            'nip' => 'DOK003',
            'no_telepon' => '081234567892',
            'tanggal_bergabung' => now()->subMonths(6),
            'is_active' => true,
            'specialty' => 'Spesialis Anak',
            'license_number' => '505/SIP/2023',
        ]);

        // Create dokter gigi user
        $this->createDokterUser([
            'name' => 'Dr. Budi Hartono, drg',
            'email' => 'dokter.gigi@dokterku.com',
            'username' => 'dokter_gigi',
            'password' => Hash::make('password'),
            'nip' => 'DOK004',
            'no_telepon' => '081234567893',
            'tanggal_bergabung' => now()->subYears(3),
            'is_active' => true,
            'specialty' => 'Dokter Gigi',
            'license_number' => '506/SIP/2019',
            'role_name' => 'dokter_gigi',
        ]);

        $this->command->info('✅ DOKTER users created successfully!');
        $this->command->info('📝 Test credentials:');
        $this->command->info('   Email: dokter@dokterku.com | Password: password');
        $this->command->info('   Email: dokter.umum@dokterku.com | Password: password');
        $this->command->info('   Email: dokter.spesialis@dokterku.com | Password: password');
        $this->command->info('   Email: dokter.gigi@dokterku.com | Password: password');
    }

    private function ensureRolesExist(): void
    {
        // Check if roles exist via Spatie Permission
        $dokterRole = SpatieRole::firstOrCreate([
            'name' => 'dokter', 
            'guard_name' => 'web',
        ], [
            'display_name' => 'Dokter',
            'description' => 'Role untuk dokter umum dan spesialis',
            'is_active' => true,
        ]);
        
        $dokterGigiRole = SpatieRole::firstOrCreate([
            'name' => 'dokter_gigi', 
            'guard_name' => 'web',
        ], [
            'display_name' => 'Dokter Gigi',
            'description' => 'Role untuk dokter gigi',
            'is_active' => true,
        ]);

        $this->command->info('✅ Roles ensured: dokter, dokter_gigi');
    }

    private function createDokterUser(array $userData): void
    {
        $roleName = $userData['role_name'] ?? 'dokter';
        $specialty = $userData['specialty'] ?? 'Umum';
        $licenseNumber = $userData['license_number'] ?? null;
        unset($userData['role_name'], $userData['specialty'], $userData['license_number']);

        // Create or update user
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            $userData
        );

        // Assign role via Spatie Permission
        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        // Create or update Dokter record
        $dokterData = [
            'user_id' => $user->id,
            'nik' => 'DOK' . date('Y') . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'nama_lengkap' => $user->name,
            'email' => $user->email,
            'username' => $userData['username'],
            'password' => $userData['password'],
            'no_telepon' => $userData['no_telepon'],
            'tanggal_bergabung' => $userData['tanggal_bergabung'],
            'status_akun' => 'aktif',
            'aktif' => true,
            'jabatan' => 'Dokter',
            'spesialisasi' => $specialty,
            'nomor_sip' => $licenseNumber ?? 'SIP/' . date('Y') . '/' . $user->id,
        ];

        $dokter = Dokter::updateOrCreate(
            ['user_id' => $user->id],
            $dokterData
        );

        $this->command->info("✅ Created dokter: {$user->name} ({$user->email})");
    }
}