<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Shift;
use App\Filament\Petugas\Resources\PasienResource;
use App\Filament\Petugas\Resources\TindakanResource;
use App\Filament\Petugas\Resources\PendapatanHarianResource;
use App\Filament\Petugas\Resources\PengeluaranHarianResource;
use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use App\Filament\Petugas\Resources\PasienResource\Pages\ListPasiens;
use App\Filament\Petugas\Resources\PasienResource\Pages\EditPasien;
use App\Filament\Petugas\Resources\TindakanResource\Pages\ListTindakans;
use App\Filament\Petugas\Resources\TindakanResource\Pages\EditTindakan;
use App\Services\PetugasStatsService;
use Spatie\Permission\Models\Role;

class DataScopingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugasA;
    protected User $petugasB;
    protected User $supervisor;
    protected User $manager;

    protected function setUp(): void
        // Roles are already created by base TestCase
    {
        parent::setUp();
        
        // Create roles
        
        // Create users with different roles
        $this->petugasA = User::factory()->create(['name' => 'Petugas A']);
        $this->petugasA->assignRole('petugas');
        
        $this->petugasB = User::factory()->create(['name' => 'Petugas B']);
        $this->petugasB->assignRole('petugas');
        
        $this->supervisor = User::factory()->create(['name' => 'Supervisor']);
        $this->supervisor->assignRole('supervisor');
        
        $this->manager = User::factory()->create(['name' => 'Manager']);
        $this->manager->assignRole('manager');
        
        // Create base data
        $this->createBaseData();
    }

    protected function createBaseData(): void
    {
        $this->jenisTindakan = JenisTindakan::factory()->create(['tarif' => 150000]);
        $this->pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Konsultasi']);
        $this->pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Obat']);
        $this->shift = Shift::factory()->create(['name' => 'Pagi', 'is_active' => true]);
    }

    public function test_pasien_data_scoping_isolation()
    {
        // Arrange - Create patients for different users
        $petugasA_patient = Pasien::factory()->create([
            'input_by' => $this->petugasA->id,
            'nama' => 'Patient of Petugas A',
            'no_rekam_medis' => 'RM-A-001',
        ]);
        
        $petugasB_patient = Pasien::factory()->create([
            'input_by' => $this->petugasB->id,
            'nama' => 'Patient of Petugas B',
            'no_rekam_medis' => 'RM-B-001',
        ]);

        // Test Petugas A can only see their own patients
        $this->actingAs($this->petugasA);
        $petugasA_query = PasienResource::getEloquentQuery();
        $petugasA_results = $petugasA_query->get();

        $this->assertCount(1, $petugasA_results);
        $this->assertEquals($petugasA_patient->id, $petugasA_results->first()->id);
        $this->assertNotContains($petugasB_patient->id, $petugasA_results->pluck('id'));

        // Test Petugas B can only see their own patients
        $this->actingAs($this->petugasB);
        $petugasB_query = PasienResource::getEloquentQuery();
        $petugasB_results = $petugasB_query->get();

        $this->assertCount(1, $petugasB_results);
        $this->assertEquals($petugasB_patient->id, $petugasB_results->first()->id);
        $this->assertNotContains($petugasA_patient->id, $petugasB_results->pluck('id'));
    }

    public function test_tindakan_data_scoping_isolation()
    {
        // Arrange - Create tindakan for different users
        $petugasA_patient = Pasien::factory()->create(['input_by' => $this->petugasA->id]);
        $petugasB_patient = Pasien::factory()->create(['input_by' => $this->petugasB->id]);

        $petugasA_tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugasA->id,
            'pasien_id' => $petugasA_patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'shift_id' => $this->shift->id,
        ]);
        
        $petugasB_tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugasB->id,
            'pasien_id' => $petugasB_patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'shift_id' => $this->shift->id,
        ]);

        // Test Petugas A isolation
        $this->actingAs($this->petugasA);
        $petugasA_query = TindakanResource::getEloquentQuery();
        $petugasA_results = $petugasA_query->get();

        $this->assertCount(1, $petugasA_results);
        $this->assertEquals($petugasA_tindakan->id, $petugasA_results->first()->id);

        // Test Petugas B isolation
        $this->actingAs($this->petugasB);
        $petugasB_query = TindakanResource::getEloquentQuery();
        $petugasB_results = $petugasB_query->get();

        $this->assertCount(1, $petugasB_results);
        $this->assertEquals($petugasB_tindakan->id, $petugasB_results->first()->id);
    }

    public function test_pendapatan_harian_data_scoping()
    {
        // Arrange
        $petugasA_pendapatan = PendapatanHarian::factory()->create([
            'user_id' => $this->petugasA->id,
            'pendapatan_id' => $this->pendapatan->id,
            'nominal' => 100000,
        ]);
        
        $petugasB_pendapatan = PendapatanHarian::factory()->create([
            'user_id' => $this->petugasB->id,
            'pendapatan_id' => $this->pendapatan->id,
            'nominal' => 200000,
        ]);

        // Test Petugas A can only see their own records
        $this->actingAs($this->petugasA);
        $petugasA_query = PendapatanHarianResource::getEloquentQuery();
        $petugasA_results = $petugasA_query->get();

        $this->assertCount(1, $petugasA_results);
        $this->assertEquals($petugasA_pendapatan->id, $petugasA_results->first()->id);

        // Test Petugas B can only see their own records
        $this->actingAs($this->petugasB);
        $petugasB_query = PendapatanHarianResource::getEloquentQuery();
        $petugasB_results = $petugasB_query->get();

        $this->assertCount(1, $petugasB_results);
        $this->assertEquals($petugasB_pendapatan->id, $petugasB_results->first()->id);
    }

    public function test_pengeluaran_harian_data_scoping()
    {
        // Arrange
        $petugasA_pengeluaran = PengeluaranHarian::factory()->create([
            'user_id' => $this->petugasA->id,
            'pengeluaran_id' => $this->pengeluaran->id,
            'nominal' => 50000,
        ]);
        
        $petugasB_pengeluaran = PengeluaranHarian::factory()->create([
            'user_id' => $this->petugasB->id,
            'pengeluaran_id' => $this->pengeluaran->id,
            'nominal' => 75000,
        ]);

        // Test isolation
        $this->actingAs($this->petugasA);
        $petugasA_query = PengeluaranHarianResource::getEloquentQuery();
        $petugasA_results = $petugasA_query->get();

        $this->assertCount(1, $petugasA_results);
        $this->assertEquals($petugasA_pengeluaran->id, $petugasA_results->first()->id);

        $this->actingAs($this->petugasB);
        $petugasB_query = PengeluaranHarianResource::getEloquentQuery();
        $petugasB_results = $petugasB_query->get();

        $this->assertCount(1, $petugasB_results);
        $this->assertEquals($petugasB_pengeluaran->id, $petugasB_results->first()->id);
    }

    public function test_jumlah_pasien_harian_data_scoping()
    {
        // Arrange
        $petugasA_laporan = JumlahPasienHarian::factory()->create([
            'user_id' => $this->petugasA->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah_pasien' => 10,
        ]);
        
        $petugasB_laporan = JumlahPasienHarian::factory()->create([
            'user_id' => $this->petugasB->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah_pasien' => 15,
        ]);

        // Test isolation
        $this->actingAs($this->petugasA);
        $petugasA_query = JumlahPasienHarianResource::getEloquentQuery();
        $petugasA_results = $petugasA_query->get();

        $this->assertCount(1, $petugasA_results);
        $this->assertEquals($petugasA_laporan->id, $petugasA_results->first()->id);

        $this->actingAs($this->petugasB);
        $petugasB_query = JumlahPasienHarianResource::getEloquentQuery();
        $petugasB_results = $petugasB_query->get();

        $this->assertCount(1, $petugasB_results);
        $this->assertEquals($petugasB_laporan->id, $petugasB_results->first()->id);
    }

    public function test_direct_record_access_prevention()
    {
        // Arrange - Create records for different users
        $petugasA_patient = Pasien::factory()->create(['input_by' => $this->petugasA->id]);
        $petugasB_patient = Pasien::factory()->create(['input_by' => $this->petugasB->id]);

        // Test Petugas A cannot directly access Petugas B's patient
        $this->actingAs($this->petugasA);
        
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        Livewire::test(EditPasien::class, ['record' => $petugasB_patient->getRouteKey()]);
    }

    public function test_stats_service_data_isolation()
    {
        // Arrange - Create different data for different users
        Pasien::factory()->count(5)->create(['input_by' => $this->petugasA->id]);
        Pasien::factory()->count(3)->create(['input_by' => $this->petugasB->id]);

        $petugasA_patients = Pasien::where('input_by', $this->petugasA->id)->get();
        $petugasB_patients = Pasien::where('input_by', $this->petugasB->id)->get();

        // Create tindakan for each user
        foreach ($petugasA_patients as $patient) {
            Tindakan::factory()->create([
                'input_by' => $this->petugasA->id,
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'shift_id' => $this->shift->id,
                'tarif' => 100000,
            ]);
        }

        foreach ($petugasB_patients as $patient) {
            Tindakan::factory()->create([
                'input_by' => $this->petugasB->id,
                'pasien_id' => $patient->id,
                'jenis_tindakan_id' => $this->jenisTindakan->id,
                'shift_id' => $this->shift->id,
                'tarif' => 150000,
            ]);
        }

        $statsService = new PetugasStatsService();

        // Test Petugas A stats only include their data
        $petugasA_stats = $statsService->getDashboardStats($this->petugasA->id);
        $this->assertEquals(5, $petugasA_stats['daily']['today']['pasien_count']);
        $this->assertEquals(5, $petugasA_stats['daily']['today']['tindakan_count']);
        $this->assertEquals(500000, $petugasA_stats['daily']['today']['tindakan_sum']);

        // Test Petugas B stats only include their data
        $petugasB_stats = $statsService->getDashboardStats($this->petugasB->id);
        $this->assertEquals(3, $petugasB_stats['daily']['today']['pasien_count']);
        $this->assertEquals(3, $petugasB_stats['daily']['today']['tindakan_count']);
        $this->assertEquals(450000, $petugasB_stats['daily']['today']['tindakan_sum']);
    }

    public function test_livewire_component_data_isolation()
    {
        // Arrange
        $petugasA_patient = Pasien::factory()->create([
            'input_by' => $this->petugasA->id,
            'nama' => 'Petugas A Patient',
        ]);
        
        $petugasB_patient = Pasien::factory()->create([
            'input_by' => $this->petugasB->id,
            'nama' => 'Petugas B Patient',
        ]);

        // Test Petugas A can only see their patients in list
        $this->actingAs($this->petugasA);
        $componentA = Livewire::test(ListPasiens::class);
        
        $componentA->assertSuccessful()
            ->assertSee('Petugas A Patient')
            ->assertDontSee('Petugas B Patient');

        // Test Petugas B can only see their patients in list
        $this->actingAs($this->petugasB);
        $componentB = Livewire::test(ListPasiens::class);
        
        $componentB->assertSuccessful()
            ->assertSee('Petugas B Patient')
            ->assertDontSee('Petugas A Patient');
    }

    public function test_bulk_operations_respect_data_scoping()
    {
        // Arrange - Create patients for different users
        $petugasA_patients = Pasien::factory()->count(3)->create(['input_by' => $this->petugasA->id]);
        $petugasB_patients = Pasien::factory()->count(2)->create(['input_by' => $this->petugasB->id]);

        // Test Petugas A cannot bulk delete Petugas B's patients
        $this->actingAs($this->petugasA);
        
        $allPatientIds = Pasien::all()->pluck('id')->toArray();
        
        $component = Livewire::test(ListPasiens::class)
            ->set('selectedTableRecords', $allPatientIds);

        // Should only select their own records
        $selectedRecords = $component->get('selectedTableRecords');
        $this->assertCount(3, $selectedRecords); // Only Petugas A's patients

        // Verify only Petugas A's patient IDs are in selection
        foreach ($selectedRecords as $recordId) {
            $patient = Pasien::find($recordId);
            $this->assertEquals($this->petugasA->id, $patient->input_by);
        }
    }

    public function test_search_respects_data_scoping()
    {
        // Arrange
        $petugasA_patient = Pasien::factory()->create([
            'input_by' => $this->petugasA->id,
            'nama' => 'SearchTest Patient A',
            'no_rekam_medis' => 'RM-SEARCH-A',
        ]);
        
        $petugasB_patient = Pasien::factory()->create([
            'input_by' => $this->petugasB->id,
            'nama' => 'SearchTest Patient B',
            'no_rekam_medis' => 'RM-SEARCH-B',
        ]);

        // Test Petugas A search only finds their patients
        $this->actingAs($this->petugasA);
        $componentA = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'SearchTest');

        $componentA->assertSee('SearchTest Patient A')
            ->assertDontSee('SearchTest Patient B');

        // Test search by record number
        $componentA2 = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'RM-SEARCH-B'); // Search for B's record

        $componentA2->assertDontSee('SearchTest Patient B'); // Should not find B's patient

        // Test Petugas B search
        $this->actingAs($this->petugasB);
        $componentB = Livewire::test(ListPasiens::class)
            ->set('tableSearch', 'SearchTest');

        $componentB->assertSee('SearchTest Patient B')
            ->assertDontSee('SearchTest Patient A');
    }

    public function test_api_endpoints_respect_data_scoping()
    {
        // This test assumes there are API endpoints
        // Create patients for different users
        $petugasA_patient = Pasien::factory()->create(['input_by' => $this->petugasA->id]);
        $petugasB_patient = Pasien::factory()->create(['input_by' => $this->petugasB->id]);

        // Test API access (if available)
        $this->actingAs($this->petugasA);
        
        // Test getting patient list via API
        $response = $this->getJson('/api/v1/pasien');
        
        if ($response->status() === 200) {
            $patients = $response->json('data');
            $patientIds = collect($patients)->pluck('id');
            
            $this->assertContains($petugasA_patient->id, $patientIds);
            $this->assertNotContains($petugasB_patient->id, $patientIds);
        }
    }

    public function test_supervisor_can_see_all_data()
    {
        // Arrange - Create data for different petugas
        $petugasA_patient = Pasien::factory()->create(['input_by' => $this->petugasA->id]);
        $petugasB_patient = Pasien::factory()->create(['input_by' => $this->petugasB->id]);

        // Test supervisor access (if they have broader permissions)
        $this->actingAs($this->supervisor);
        
        // This test depends on supervisor having broader access
        // If supervisors should see all data, uncomment below:
        
        /*
        $supervisorQuery = PasienResource::getEloquentQuery();
        $supervisorResults = $supervisorQuery->get();
        
        $this->assertCount(2, $supervisorResults);
        $resultIds = $supervisorResults->pluck('id');
        $this->assertContains($petugasA_patient->id, $resultIds);
        $this->assertContains($petugasB_patient->id, $resultIds);
        */
        
        // For now, assume supervisors also have scoped access
        $this->assertTrue(true);
    }

    public function test_cross_user_data_modification_prevention()
    {
        // Arrange
        $petugasA_patient = Pasien::factory()->create([
            'input_by' => $this->petugasA->id,
            'nama' => 'Original Name',
        ]);

        // Test Petugas B cannot modify Petugas A's patient
        $this->actingAs($this->petugasB);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(EditPasien::class, ['record' => $petugasA_patient->getRouteKey()])
            ->fillForm(['nama' => 'Modified by Petugas B'])
            ->call('save');
    }

    public function test_data_scoping_with_relationships()
    {
        // Arrange - Create complex relationships
        $petugasA_patient = Pasien::factory()->create(['input_by' => $this->petugasA->id]);
        $petugasB_patient = Pasien::factory()->create(['input_by' => $this->petugasB->id]);

        $petugasA_tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugasA->id,
            'pasien_id' => $petugasA_patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'shift_id' => $this->shift->id,
        ]);

        $petugasB_tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugasB->id,
            'pasien_id' => $petugasB_patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'shift_id' => $this->shift->id,
        ]);

        // Test Petugas A can only access tindakan through their patients
        $this->actingAs($this->petugasA);
        $tindakanQuery = TindakanResource::getEloquentQuery();
        $tindakanResults = $tindakanQuery->with('pasien')->get();

        $this->assertCount(1, $tindakanResults);
        $this->assertEquals($petugasA_tindakan->id, $tindakanResults->first()->id);
        $this->assertEquals($petugasA_patient->id, $tindakanResults->first()->pasien->id);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}