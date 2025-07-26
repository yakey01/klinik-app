<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\DiParamedis;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class DiParamedisTest extends TestCase
{
    use RefreshDatabase;

    protected $paramedisUser;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $paramedisRole = Role::create(['name' => 'paramedis', 'display_name' => 'Paramedis']);
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);

        // Create paramedis pegawai and user
        $pegawai = Pegawai::factory()->create([
            'jenis_pegawai' => 'Paramedis',
            'nama_lengkap' => 'Test Paramedis',
            'aktif' => true,
        ]);

        $this->paramedisUser = User::factory()->create([
            'pegawai_id' => $pegawai->id,
            'name' => $pegawai->nama_lengkap,
            'role_id' => $paramedisRole->id,
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'role_id' => $adminRole->id,
        ]);
    }

    /** @test */
    public function paramedis_can_create_di_paramedis()
    {
        Sanctum::actingAs($this->paramedisUser);

        $response = $this->postJson('/api/v2/dashboards/paramedis/di-paramedis', [
            'tanggal' => now()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'shift' => 'Pagi',
            'lokasi_tugas' => 'IGD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'pegawai_id',
                    'tanggal',
                    'jam_mulai',
                    'shift',
                    'lokasi_tugas',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'draft',
                ],
            ]);

        $this->assertDatabaseHas('di_paramedis', [
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function paramedis_can_update_their_draft_di()
    {
        Sanctum::actingAs($this->paramedisUser);

        $di = DiParamedis::factory()->draft()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'user_id' => $this->paramedisUser->id,
        ]);

        $response = $this->putJson("/api/v2/dashboards/paramedis/di-paramedis/{$di->id}", [
            'jumlah_pasien_dilayani' => 10,
            'jumlah_tindakan_medis' => 5,
            'jumlah_observasi_pasien' => 15,
            'laporan_kegiatan' => 'Melakukan pelayanan pasien di IGD',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'jumlah_pasien_dilayani' => 10,
                    'jumlah_tindakan_medis' => 5,
                ],
            ]);
    }

    /** @test */
    public function paramedis_cannot_update_submitted_di()
    {
        Sanctum::actingAs($this->paramedisUser);

        $di = DiParamedis::factory()->submitted()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'user_id' => $this->paramedisUser->id,
        ]);

        $response = $this->putJson("/api/v2/dashboards/paramedis/di-paramedis/{$di->id}", [
            'jumlah_pasien_dilayani' => 10,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function paramedis_can_submit_di_for_approval()
    {
        Sanctum::actingAs($this->paramedisUser);

        $di = DiParamedis::factory()->draft()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'user_id' => $this->paramedisUser->id,
            'jumlah_pasien_dilayani' => 10,
            'jumlah_tindakan_medis' => 5,
            'jumlah_observasi_pasien' => 15,
            'laporan_kegiatan' => 'Laporan kegiatan harian',
        ]);

        $response = $this->postJson("/api/v2/dashboards/paramedis/di-paramedis/{$di->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'submitted',
                ],
            ]);

        $this->assertDatabaseHas('di_paramedis', [
            'id' => $di->id,
            'status' => 'submitted',
        ]);
    }

    /** @test */
    public function paramedis_can_add_tindakan_medis()
    {
        Sanctum::actingAs($this->paramedisUser);

        $di = DiParamedis::factory()->draft()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'user_id' => $this->paramedisUser->id,
        ]);

        $response = $this->postJson("/api/v2/dashboards/paramedis/di-paramedis/{$di->id}/tindakan", [
            'nama_tindakan' => 'Injeksi IM',
            'jumlah' => 3,
            'keterangan' => 'Pemberian antibiotik',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $di->refresh();
        $this->assertCount(1, $di->tindakan_medis);
        $this->assertEquals('Injeksi IM', $di->tindakan_medis[0]['nama_tindakan']);
    }

    /** @test */
    public function paramedis_can_view_summary()
    {
        Sanctum::actingAs($this->paramedisUser);

        // Create some DI records
        DiParamedis::factory()->count(5)->approved()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'tanggal' => now()->startOfMonth(),
        ]);

        $response = $this->getJson('/api/v2/dashboards/paramedis/di-paramedis/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period' => ['month', 'year', 'month_name'],
                    'summary' => [
                        'total_hari',
                        'total_approved',
                        'total_pasien',
                        'total_tindakan',
                    ],
                ],
            ]);
    }

    /** @test */
    public function non_paramedis_cannot_access_di_endpoints()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v2/dashboards/paramedis/di-paramedis');

        $response->assertStatus(403);
    }

    /** @test */
    public function paramedis_cannot_create_duplicate_di_for_same_date()
    {
        Sanctum::actingAs($this->paramedisUser);

        $date = now()->format('Y-m-d');

        // Create first DI
        DiParamedis::factory()->create([
            'pegawai_id' => $this->paramedisUser->pegawai_id,
            'user_id' => $this->paramedisUser->id,
            'tanggal' => $date,
        ]);

        // Try to create another DI for the same date
        $response = $this->postJson('/api/v2/dashboards/paramedis/di-paramedis', [
            'tanggal' => $date,
            'jam_mulai' => '08:00',
            'shift' => 'Pagi',
            'lokasi_tugas' => 'IGD',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }
}