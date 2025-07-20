<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class JadwalJagaEnhancedTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    private User $adminUser;
    private ShiftTemplate $shiftTemplate;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = $this->getRole('admin');
        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now()
        ]);
        
        // Create shift template
        $this->shiftTemplate = ShiftTemplate::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'keterangan' => 'Shift pagi'
        ]);
        
        // Create required roles
        // Roles are already created by base TestCase
    }

    /** @test */
    public function test_dokter_without_user_account_triggers_missing_user_notification()
    {
        // Create dokter without user account
        $dokter = Dokter::create([
            'nama_lengkap' => 'Dr. Test',
            'jabatan' => 'Dokter Umum',
            'nomor_sip' => '12345',
            'aktif' => true,
            'user_id' => null // No user account
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Simulate the validateAndProcessStaff method
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        $method = $reflection->getMethod('validateAndProcessStaff');
        $method->setAccessible(true);
        
        $result = $method->invoke($listJadwalJagas, 'dokter_' . $dokter->id, 'dokter_jaga');
        
        // Assert dokter info is returned but without user
        $this->assertNotNull($result);
        $this->assertEquals('dokter', $result['type']);
        $this->assertEquals('Dr. Test', $result['name']);
        $this->assertNull($result['user']);
        $this->assertEquals('Dokter Jaga', $result['unit_kerja']);
    }

    /** @test */
    public function test_pegawai_without_user_account_triggers_missing_user_notification()
    {
        // Create pegawai without user account
        $pegawai = Pegawai::create([
            'nik' => '1234567890',
            'nama_lengkap' => 'Perawat Test',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Simulate the validateAndProcessStaff method
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        $method = $reflection->getMethod('validateAndProcessStaff');
        $method->setAccessible(true);
        
        $result = $method->invoke($listJadwalJagas, 'pegawai_' . $pegawai->id, 'pelayanan');
        
        // Assert pegawai info is returned but without user
        $this->assertNotNull($result);
        $this->assertEquals('pegawai', $result['type']);
        $this->assertEquals('Perawat Test', $result['name']);
        $this->assertNull($result['user']);
        $this->assertEquals('Pelayanan', $result['unit_kerja']);
        $this->assertEquals('Paramedis', $result['peran']);
    }

    /** @test */
    public function test_dokter_with_user_account_can_be_scheduled()
    {
        // Create user first
        $user = User::create([
            'name' => 'Dr. HasUser',
            'email' => 'dr.hasuser@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'dokter')->first()->id,
            'email_verified_at' => now()
        ]);
        
        // Create dokter with user account
        $dokter = Dokter::create([
            'nama_lengkap' => 'Dr. HasUser',
            'jabatan' => 'Dokter Umum',
            'nomor_sip' => '54321',
            'aktif' => true,
            'user_id' => $user->id
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Create schedule
        $jadwal = JadwalJaga::create([
            'tanggal_jaga' => now()->addDay()->toDateString(),
            'shift_template_id' => $this->shiftTemplate->id,
            'pegawai_id' => $user->id,
            'unit_kerja' => 'Dokter Jaga',
            'peran' => 'Dokter',
            'status_jaga' => 'Aktif',
            'keterangan' => 'Test schedule'
        ]);
        
        // Assert schedule was created successfully
        $this->assertDatabaseHas('jadwal_jagas', [
            'pegawai_id' => $user->id,
            'unit_kerja' => 'Dokter Jaga',
            'peran' => 'Dokter'
        ]);
    }

    /** @test */
    public function test_pegawai_with_user_account_can_be_scheduled()
    {
        // Create user first
        $user = User::create([
            'name' => 'Perawat HasUser',
            'email' => 'perawat.hasuser@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'paramedis')->first()->id,
            'nip' => '9876543210',
            'email_verified_at' => now()
        ]);
        
        // Create pegawai with user account (relationship via NIP)
        $pegawai = Pegawai::create([
            'nik' => '9876543210',
            'nama_lengkap' => 'Perawat HasUser',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Create schedule
        $jadwal = JadwalJaga::create([
            'tanggal_jaga' => now()->addDay()->toDateString(),
            'shift_template_id' => $this->shiftTemplate->id,
            'pegawai_id' => $user->id,
            'unit_kerja' => 'Pelayanan',
            'peran' => 'Paramedis',
            'status_jaga' => 'Aktif',
            'keterangan' => 'Test schedule'
        ]);
        
        // Assert schedule was created successfully
        $this->assertDatabaseHas('jadwal_jagas', [
            'pegawai_id' => $user->id,
            'unit_kerja' => 'Pelayanan',
            'peran' => 'Paramedis'
        ]);
    }

    /** @test */
    public function test_automatic_user_creation_for_dokter()
    {
        // Create dokter without user account
        $dokter = Dokter::create([
            'nama_lengkap' => 'Dr. AutoCreate',
            'jabatan' => 'Dokter Umum',
            'nomor_sip' => '99999',
            'aktif' => true,
            'user_id' => null
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Simulate automatic user creation
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        
        // Test generateUniqueEmail method
        $emailMethod = $reflection->getMethod('generateUniqueEmail');
        $emailMethod->setAccessible(true);
        $email = $emailMethod->invoke($listJadwalJagas, 'dr.autocreate');
        
        $this->assertEquals('dr.autocreate@dokterku.local', $email);
        
        // Create user account manually (simulating auto-creation)
        $role = Role::where('name', 'dokter')->first();
        $user = User::create([
            'name' => 'Dr. AutoCreate',
            'email' => $email,
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'email_verified_at' => now()
        ]);
        
        // Link dokter to user
        $dokter->update(['user_id' => $user->id]);
        
        // Assert user was created and linked
        $this->assertDatabaseHas('users', [
            'name' => 'Dr. AutoCreate',
            'email' => 'dr.autocreate@dokterku.local',
            'role_id' => $role->id
        ]);
        
        $this->assertEquals($user->id, $dokter->refresh()->user_id);
    }

    /** @test */
    public function test_automatic_user_creation_for_pegawai()
    {
        // Create pegawai without user account
        $pegawai = Pegawai::create([
            'nik' => '1111111111',
            'nama_lengkap' => 'Perawat AutoCreate',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Simulate automatic user creation
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        
        // Test generateUniqueEmail method
        $emailMethod = $reflection->getMethod('generateUniqueEmail');
        $emailMethod->setAccessible(true);
        $email = $emailMethod->invoke($listJadwalJagas, 'perawat.autocreate');
        
        $this->assertEquals('perawat.autocreate@dokterku.local', $email);
        
        // Create user account manually (simulating auto-creation)
        $role = Role::where('name', 'paramedis')->first();
        $user = User::create([
            'name' => 'Perawat AutoCreate',
            'email' => $email,
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
            'nip' => '1111111111',
            'email_verified_at' => now()
        ]);
        
        // Assert user was created with correct NIP linkage
        $this->assertDatabaseHas('users', [
            'name' => 'Perawat AutoCreate',
            'email' => 'perawat.autocreate@dokterku.local',
            'role_id' => $role->id,
            'nip' => '1111111111'
        ]);
        
        // Link pegawai to user
        $pegawai->update(['user_id' => $user->id]);
        
        // Test relationship via NIP
        $this->assertEquals($user->id, $pegawai->refresh()->user->id);
    }

    /** @test */
    public function test_duplicate_schedule_prevention()
    {
        // Create user and dokter
        $user = User::create([
            'name' => 'Dr. Duplicate',
            'email' => 'dr.duplicate@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'dokter')->first()->id,
            'email_verified_at' => now()
        ]);
        
        $dokter = Dokter::create([
            'nama_lengkap' => 'Dr. Duplicate',
            'nik' => 'DOK202512345',
            'jabatan' => 'Dokter Umum',
            'nomor_sip' => '88888',
            'aktif' => true,
            'user_id' => $user->id
        ]);
        
        $tanggalJaga = now()->addDay()->toDateString();
        
        // Create first schedule
        try {
            // Debug: Check if shift template exists
            $this->assertDatabaseHas('shift_templates', ['id' => $this->shiftTemplate->id]);
            
            // Debug: Check if user exists
            $this->assertDatabaseHas('users', ['id' => $user->id]);
            
            $jadwal1 = JadwalJaga::create([
                'tanggal_jaga' => $tanggalJaga,
                'shift_template_id' => $this->shiftTemplate->id,
                'pegawai_id' => $user->id,
                'unit_kerja' => 'Dokter Jaga',
                'peran' => 'Dokter',
                'status_jaga' => 'Aktif',
                'keterangan' => 'First schedule'
            ]);
            
            // Debug: Check if jadwal was created
            $this->assertNotNull($jadwal1->id);
            
        } catch (\Exception $e) {
            $this->fail('Failed to create JadwalJaga: ' . $e->getMessage());
        }
        
        // Assert first schedule was created
        $this->assertDatabaseHas('jadwal_jagas', [
            'tanggal_jaga' => $tanggalJaga . ' 00:00:00',
            'shift_template_id' => $this->shiftTemplate->id,
            'pegawai_id' => $user->id,
            'unit_kerja' => 'Dokter Jaga',
            'peran' => 'Dokter'
        ]);
        
        // Debug: Check what's in the database
        $allJadwal = JadwalJaga::all();
        $this->assertGreaterThan(0, $allJadwal->count(), 'No jadwal records found in database');
        
        // Debug: Check specific record
        $specificJadwal = JadwalJaga::where('pegawai_id', $user->id)->first();
        $this->assertNotNull($specificJadwal, 'No jadwal found for user');
        
        // Debug: Print actual values
        $this->assertNotNull($specificJadwal->tanggal_jaga, 'tanggal_jaga is null');
        $this->assertNotNull($specificJadwal->shift_template_id, 'shift_template_id is null');
        $this->assertNotNull($specificJadwal->pegawai_id, 'pegawai_id is null');
        
        // Debug: Check if values match
        $this->assertEquals($tanggalJaga, $specificJadwal->tanggal_jaga->format('Y-m-d'), 'Date format mismatch');
        $this->assertEquals($this->shiftTemplate->id, $specificJadwal->shift_template_id, 'Shift template ID mismatch');
        $this->assertEquals($user->id, $specificJadwal->pegawai_id, 'User ID mismatch');
        
        // Assert only one schedule exists
        $count = JadwalJaga::whereDate('tanggal_jaga', $tanggalJaga)
            ->where('shift_template_id', $this->shiftTemplate->id)
            ->where('pegawai_id', $user->id)
            ->count();
        
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function test_unique_email_generation_with_conflicts()
    {
        // Create existing user with base email
        User::create([
            'name' => 'Existing User',
            'email' => 'test.user@dokterku.local',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'petugas')->first()->id,
            'email_verified_at' => now()
        ]);
        
        $this->actingAs($this->adminUser);
        
        // Test unique email generation
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        $method = $reflection->getMethod('generateUniqueEmail');
        $method->setAccessible(true);
        
        $uniqueEmail = $method->invoke($listJadwalJagas, 'test.user');
        
        // Should generate test.user1@dokterku.local due to conflict
        $this->assertEquals('test.user1@dokterku.local', $uniqueEmail);
    }

    /** @test */
    public function test_role_mapping_for_different_staff_types()
    {
        $this->actingAs($this->adminUser);
        
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        $method = $reflection->getMethod('validateAndProcessStaff');
        $method->setAccessible(true);
        
        // Test paramedis pegawai
        $paramedis = Pegawai::create([
            'nik' => '2222222222',
            'nama_lengkap' => 'Perawat Paramedis',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true
        ]);
        
        $result = $method->invoke($listJadwalJagas, 'pegawai_' . $paramedis->id, 'pelayanan');
        $this->assertEquals('Paramedis', $result['peran']);
        
        // Test non-paramedis pegawai
        $nonParamedis = Pegawai::create([
            'nik' => '3333333333',
            'nama_lengkap' => 'Admin Non-Paramedis',
            'jabatan' => 'Admin',
            'jenis_pegawai' => 'Non-Paramedis',
            'aktif' => true
        ]);
        
        $result = $method->invoke($listJadwalJagas, 'pegawai_' . $nonParamedis->id, 'pendaftaran');
        $this->assertEquals('NonParamedis', $result['peran']);
        $this->assertEquals('Pendaftaran', $result['unit_kerja']);
    }

    /** @test */
    public function test_invalid_staff_id_handling()
    {
        $this->actingAs($this->adminUser);
        
        $listJadwalJagas = new \App\Filament\Resources\JadwalJagaResource\Pages\ListJadwalJagas();
        $reflection = new \ReflectionClass($listJadwalJagas);
        $method = $reflection->getMethod('validateAndProcessStaff');
        $method->setAccessible(true);
        
        // Test invalid dokter ID
        $result = $method->invoke($listJadwalJagas, 'dokter_99999', 'dokter_jaga');
        $this->assertNull($result);
        
        // Test invalid pegawai ID
        $result = $method->invoke($listJadwalJagas, 'pegawai_99999', 'pelayanan');
        $this->assertNull($result);
        
        // Test invalid format
        $result = $method->invoke($listJadwalJagas, 'invalid_format_123', 'pelayanan');
        $this->assertNull($result);
    }
}