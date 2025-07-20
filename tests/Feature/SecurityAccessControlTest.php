<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecurityAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $petugasUser;
    private User $bendaharaUser;
    private User $adminUser;
    private User $dokterUser;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $petugasRole = $this->getRole('petugas');
        $bendaharaRole = $this->getRole('bendahara');
        $adminRole = $this->getRole('admin');
        $dokterRole = $this->getRole('dokter');
        $unauthorizedRole = $this->getRole('guest');
        
        // Create test users with different roles
        $this->petugasUser = User::create([
            'name' => 'Test Petugas',
            'email' => 'petugas@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $petugasRole->id,
            'is_active' => true,
        ]);
        
        $this->bendaharaUser = User::create([
            'name' => 'Test Bendahara',
            'email' => 'bendahara@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $bendaharaRole->id,
            'is_active' => true,
        ]);
        
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        
        $this->dokterUser = User::create([
            'name' => 'Dr. Test',
            'email' => 'dokter@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $dokterRole->id,
            'is_active' => true,
        ]);
        
        $this->unauthorizedUser = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $unauthorizedRole->id,
            'is_active' => false, // Inactive user
        ]);
    }

    public function test_authenticated_user_access_control()
    {
        // Test unauthenticated access is denied
        $response = $this->get('/petugas');
        $response->assertRedirect('/petugas/login');
        
        // Test authenticated access works
        $this->actingAs($this->petugasUser);
        $response = $this->get('/petugas');
        $response->assertStatus(200);
        
        // Test inactive user is denied access
        $this->actingAs($this->unauthorizedUser);
        $response = $this->get('/petugas');
        $response->assertStatus(403); // Or redirect based on implementation
    }

    public function test_role_based_panel_access_control()
    {
        // Test petugas can access petugas panel
        $this->actingAs($this->petugasUser);
        $response = $this->get('/petugas');
        $response->assertStatus(200);
        
        // Test petugas cannot access bendahara panel
        $response = $this->get('/bendahara');
        $response->assertStatus(403);
        
        // Test bendahara can access bendahara panel
        $this->actingAs($this->bendaharaUser);
        $response = $this->get('/bendahara');
        $response->assertStatus(200);
        
        // Test bendahara cannot access admin panel
        $response = $this->get('/admin');
        $response->assertStatus(403);
        
        // Test admin can access admin panel
        $this->actingAs($this->adminUser);
        $response = $this->get('/admin');
        $response->assertStatus(200);
        
        // Test admin can access other panels (admin privilege)
        $response = $this->get('/petugas');
        $response->assertStatus(200);
        
        $response = $this->get('/bendahara');
        $response->assertStatus(200);
    }

    public function test_patient_data_access_control()
    {
        // Create test patient
        $patient = Pasien::create([
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_telepon' => '08123456789',
            'email' => 'john@example.com',
        ]);
        
        // Test petugas can view patients
        $this->actingAs($this->petugasUser);
        $response = $this->get("/petugas/pasien/{$patient->id}");
        $response->assertStatus(200);
        
        // Test unauthorized user cannot view patient data
        $this->actingAs($this->unauthorizedUser);
        $response = $this->get("/petugas/pasien/{$patient->id}");
        $response->assertStatus(403);
        
        // Test dokter can view patient data (medical access)
        $this->actingAs($this->dokterUser);
        $response = $this->get("/dokter/pasien/{$patient->id}");
        $response->assertStatus(200);
    }

    public function test_financial_data_access_control()
    {
        $this->actingAs($this->petugasUser);
        
        // Create test financial data
        $pendapatan = Pendapatan::create([
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Test pendapatan',
            'jumlah' => 100000,
            'status' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        $pengeluaran = Pengeluaran::create([
            'kategori' => 'operasional',
            'keterangan' => 'Test pengeluaran',
            'jumlah' => 50000,
            'status' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Test petugas can view basic financial data they created
        $response = $this->get("/petugas/pendapatan/{$pendapatan->id}");
        $response->assertStatus(200);
        
        // Test bendahara can view and validate financial data
        $this->actingAs($this->bendaharaUser);
        $response = $this->get("/bendahara/pendapatan/{$pendapatan->id}");
        $response->assertStatus(200);
        
        $response = $this->get("/bendahara/pengeluaran/{$pengeluaran->id}");
        $response->assertStatus(200);
        
        // Test dokter cannot access financial data
        $this->actingAs($this->dokterUser);
        $response = $this->get("/bendahara/pendapatan/{$pendapatan->id}");
        $response->assertStatus(403);
        
        // Test unauthorized user cannot access financial data
        $this->actingAs($this->unauthorizedUser);
        $response = $this->get("/petugas/pendapatan/{$pendapatan->id}");
        $response->assertStatus(403);
    }

    public function test_validation_permission_control()
    {
        $this->actingAs($this->petugasUser);
        
        // Create tindakan
        $tindakan = Tindakan::create([
            'pasien_id' => Pasien::create([
                'no_rekam_medis' => 'RM002',
                'nama' => 'Jane Doe',
                'tanggal_lahir' => '1985-05-15',
                'jenis_kelamin' => 'P',
                'alamat' => 'Bandung',
            ])->id,
            'jenis_tindakan_id' => 1, // Assuming exists
            'dokter_id' => $this->dokterUser->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => 100000,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Test petugas cannot validate their own tindakan
        $response = $this->patch("/petugas/tindakan/{$tindakan->id}/validate", [
            'status_validasi' => 'approved',
            'komentar_validasi' => 'Approved by petugas',
        ]);
        $response->assertStatus(403); // Should be forbidden
        
        // Test bendahara can validate tindakan
        $this->actingAs($this->bendaharaUser);
        $response = $this->patch("/bendahara/tindakan/{$tindakan->id}/validate", [
            'status_validasi' => 'approved',
            'komentar_validasi' => 'Approved by bendahara',
        ]);
        $response->assertStatus(200);
        
        // Verify validation was applied
        $tindakan->refresh();
        $this->assertEquals('approved', $tindakan->status_validasi);
        $this->assertEquals($this->bendaharaUser->id, $tindakan->validated_by);
        
        // Test dokter cannot validate financial aspects
        $this->actingAs($this->dokterUser);
        $newTindakan = Tindakan::create([
            'pasien_id' => $tindakan->pasien_id,
            'jenis_tindakan_id' => 1,
            'dokter_id' => $this->dokterUser->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => 150000,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->dokterUser->id,
        ]);
        
        $response = $this->patch("/dokter/tindakan/{$newTindakan->id}/validate", [
            'status_validasi' => 'approved',
        ]);
        $response->assertStatus(403); // Dokter shouldn't validate financial aspects
    }

    public function test_admin_override_permissions()
    {
        $this->actingAs($this->adminUser);
        
        // Create test data
        $patient = Pasien::create([
            'no_rekam_medis' => 'RM003',
            'nama' => 'Admin Test Patient',
            'tanggal_lahir' => '1995-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
        ]);
        
        $pendapatan = Pendapatan::create([
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Admin test pendapatan',
            'jumlah' => 200000,
            'status' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Test admin can access all panels
        $response = $this->get('/admin');
        $response->assertStatus(200);
        
        $response = $this->get('/petugas');
        $response->assertStatus(200);
        
        $response = $this->get('/bendahara');
        $response->assertStatus(200);
        
        $response = $this->get('/dokter');
        $response->assertStatus(200);
        
        // Test admin can view all data
        $response = $this->get("/admin/pasien/{$patient->id}");
        $response->assertStatus(200);
        
        $response = $this->get("/admin/pendapatan/{$pendapatan->id}");
        $response->assertStatus(200);
        
        // Test admin can perform validation
        $tindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => 1,
            'dokter_id' => $this->dokterUser->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => 100000,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        $response = $this->patch("/admin/tindakan/{$tindakan->id}/validate", [
            'status_validasi' => 'approved',
            'komentar_validasi' => 'Admin override approval',
        ]);
        $response->assertStatus(200);
        
        // Test admin can escalate overdue validations
        $overdueTindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => 1,
            'dokter_id' => $this->dokterUser->id,
            'tanggal_tindakan' => Carbon::now()->subDays(10),
            'tarif' => 100000,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
            'created_at' => Carbon::now()->subDays(8), // 8 days old
        ]);
        
        $response = $this->patch("/admin/tindakan/{$overdueTindakan->id}/escalate", [
            'status_validasi' => 'escalated',
            'komentar_validasi' => 'ESCALATED: Validation overdue, requires immediate attention',
        ]);
        $response->assertStatus(200);
    }

    public function test_data_isolation_and_ownership()
    {
        // Create data as petugas
        $this->actingAs($this->petugasUser);
        
        $myPendapatan = Pendapatan::create([
            'kategori' => 'tindakan_medis',
            'keterangan' => 'My pendapatan',
            'jumlah' => 100000,
            'status' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Create data as different petugas (simulate another user)
        $otherPetugasUser = User::create([
            'name' => 'Other Petugas',
            'email' => 'other_petugas@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->petugasUser->role_id,
            'is_active' => true,
        ]);
        
        $this->actingAs($otherPetugasUser);
        
        $otherPendapatan = Pendapatan::create([
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Other pendapatan',
            'jumlah' => 150000,
            'status' => 'pending',
            'input_by' => $otherPetugasUser->id,
        ]);
        
        // Test that petugas can only see their own data
        $this->actingAs($this->petugasUser);
        $response = $this->get("/petugas/pendapatan/{$myPendapatan->id}");
        $response->assertStatus(200);
        
        // Test that petugas cannot see other user's data
        $response = $this->get("/petugas/pendapatan/{$otherPendapatan->id}");
        $response->assertStatus(403); // Should be forbidden
        
        // Test that bendahara can see all data for validation
        $this->actingAs($this->bendaharaUser);
        $response = $this->get("/bendahara/pendapatan/{$myPendapatan->id}");
        $response->assertStatus(200);
        
        $response = $this->get("/bendahara/pendapatan/{$otherPendapatan->id}");
        $response->assertStatus(200);
        
        // Test that admin can see all data
        $this->actingAs($this->adminUser);
        $response = $this->get("/admin/pendapatan/{$myPendapatan->id}");
        $response->assertStatus(200);
        
        $response = $this->get("/admin/pendapatan/{$otherPendapatan->id}");
        $response->assertStatus(200);
    }

    public function test_session_management_and_security()
    {
        // Test successful login
        $response = $this->post('/login', [
            'email' => $this->petugasUser->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/petugas');
        $this->assertAuthenticatedAs($this->petugasUser);
        
        // Test failed login with wrong password
        $response = $this->post('/login', [
            'email' => $this->petugasUser->email,
            'password' => 'wrongpassword',
        ]);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        
        // Test login with inactive user
        $response = $this->post('/login', [
            'email' => $this->unauthorizedUser->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors();
        $this->assertGuest();
        
        // Test logout functionality
        $this->actingAs($this->petugasUser);
        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_api_authentication_and_authorization()
    {
        // Test API access without authentication
        $response = $this->getJson('/api/pasien');
        $response->assertStatus(401); // Unauthorized
        
        // Test API access with authentication
        $this->actingAs($this->petugasUser);
        $response = $this->getJson('/api/pasien');
        $response->assertStatus(200);
        
        // Test API access with wrong role
        $this->actingAs($this->dokterUser);
        $response = $this->getJson('/api/pendapatan');
        $response->assertStatus(403); // Forbidden - dokter shouldn't access financial API
        
        // Test API access with correct role
        $this->actingAs($this->bendaharaUser);
        $response = $this->getJson('/api/pendapatan');
        $response->assertStatus(200);
    }

    public function test_input_validation_and_sanitization()
    {
        $this->actingAs($this->petugasUser);
        
        // Test SQL injection protection
        $response = $this->post('/petugas/pasien', [
            'nama' => "'; DROP TABLE pasien; --",
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_rekam_medis' => 'RM004',
        ]);
        
        // Should either validate and sanitize, or reject the input
        $this->assertTrue($response->isSuccessful() || $response->isRedirection());
        
        // Verify table still exists and no SQL injection occurred
        $this->assertDatabaseMissing('pasien', [
            'nama' => "'; DROP TABLE pasien; --",
        ]);
        
        // Test XSS protection
        $response = $this->post('/petugas/pasien', [
            'nama' => '<script>alert("XSS")</script>',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_rekam_medis' => 'RM005',
        ]);
        
        // Input should be sanitized or rejected
        $this->assertTrue($response->isSuccessful() || $response->isRedirection());
        
        // Test required field validation
        $response = $this->post('/petugas/pasien', [
            'nama' => '', // Empty required field
            'tanggal_lahir' => '1990-01-01',
        ]);
        
        $response->assertSessionHasErrors(['nama']);
        
        // Test data type validation
        $response = $this->post('/petugas/pendapatan', [
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Test pendapatan',
            'jumlah' => 'not_a_number', // Invalid numeric field
            'status' => 'pending',
        ]);
        
        $response->assertSessionHasErrors(['jumlah']);
    }

    public function test_concurrent_access_and_data_integrity()
    {
        $this->actingAs($this->petugasUser);
        
        // Create initial data
        $pendapatan = Pendapatan::create([
            'kategori' => 'tindakan_medis',
            'keterangan' => 'Concurrent test',
            'jumlah' => 100000,
            'status' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Simulate concurrent access - User 1 tries to update
        $this->actingAs($this->bendaharaUser);
        $response1 = $this->patch("/bendahara/pendapatan/{$pendapatan->id}", [
            'status' => 'approved',
            'validasi_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
        ]);
        
        // Simulate concurrent access - User 2 tries to update (admin override)
        $this->actingAs($this->adminUser);
        $response2 = $this->patch("/admin/pendapatan/{$pendapatan->id}", [
            'status' => 'rejected',
            'validasi_by' => $this->adminUser->id,
            'validated_at' => Carbon::now(),
        ]);
        
        // Both should succeed but the last one should win
        $this->assertTrue($response1->isSuccessful());
        $this->assertTrue($response2->isSuccessful());
        
        // Verify final state
        $pendapatan->refresh();
        $this->assertEquals('rejected', $pendapatan->status);
        $this->assertEquals($this->adminUser->id, $pendapatan->validasi_by);
    }

    public function test_audit_trail_and_logging()
    {
        $this->actingAs($this->petugasUser);
        
        // Create data that should be logged
        $patient = Pasien::create([
            'no_rekam_medis' => 'RM006',
            'nama' => 'Audit Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
        ]);
        
        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Pasien::class,
            'model_id' => $patient->id,
            'action' => 'created',
        ]);
        
        // Update patient
        $patient->update(['alamat' => 'Bandung']);
        
        // Verify update was logged
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Pasien::class,
            'model_id' => $patient->id,
            'action' => 'updated',
        ]);
        
        // Test validation action logging
        $this->actingAs($this->bendaharaUser);
        
        $tindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => 1,
            'dokter_id' => $this->dokterUser->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => 100000,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        $tindakan->update([
            'status_validasi' => 'approved',
            'validated_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
        ]);
        
        // Verify validation was logged
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Tindakan::class,
            'model_id' => $tindakan->id,
            'action' => 'updated',
        ]);
    }
}