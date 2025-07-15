<?php

namespace Tests\Unit\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\User;
use Carbon\Carbon;

class PasienTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_patient()
    {
        $patientData = [
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_telepon' => '08123456789',
            'email' => 'john@example.com',
            'pekerjaan' => 'Engineer',
            'status_pernikahan' => 'Single',
            'kontak_darurat_nama' => 'Jane Doe',
            'kontak_darurat_telepon' => '08987654321',
        ];

        $patient = Pasien::create($patientData);

        $this->assertInstanceOf(Pasien::class, $patient);
        $this->assertEquals('RM001', $patient->no_rekam_medis);
        $this->assertEquals('John Doe', $patient->nama);
        $this->assertEquals('L', $patient->jenis_kelamin);
        $this->assertDatabaseHas('pasien', [
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
        ]);
    }

    /** @test */
    public function it_can_calculate_patient_age()
    {
        $birthDate = Carbon::now()->subYears(25);
        $patient = Pasien::factory()->create([
            'tanggal_lahir' => $birthDate,
        ]);

        $this->assertEquals(25, $patient->umur);
    }

    /** @test */
    public function it_has_tindakan_relationship()
    {
        $patient = Pasien::factory()->create();
        $tindakan = Tindakan::factory()->create(['pasien_id' => $patient->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $patient->tindakan);
        $this->assertEquals($tindakan->id, $patient->tindakan->first()->id);
    }

    /** @test */
    public function it_can_scope_by_gender()
    {
        Pasien::factory()->create(['jenis_kelamin' => 'L']);
        Pasien::factory()->create(['jenis_kelamin' => 'P']);
        Pasien::factory()->create(['jenis_kelamin' => 'L']);

        $malePatients = Pasien::byGender('L')->get();
        $femalePatients = Pasien::byGender('P')->get();

        $this->assertCount(2, $malePatients);
        $this->assertCount(1, $femalePatients);
    }

    /** @test */
    public function it_can_get_cached_statistics()
    {
        // Create test data
        Pasien::factory()->create(['jenis_kelamin' => 'L', 'created_at' => today()]);
        Pasien::factory()->create(['jenis_kelamin' => 'P', 'created_at' => today()]);
        Pasien::factory()->create(['jenis_kelamin' => 'L', 'created_at' => yesterday()]);

        $stats = Pasien::getCachedStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('male_count', $stats);
        $this->assertArrayHasKey('female_count', $stats);
        $this->assertArrayHasKey('recent_count', $stats);
        $this->assertArrayHasKey('avg_age', $stats);
        
        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(2, $stats['male_count']);
        $this->assertEquals(1, $stats['female_count']);
        $this->assertEquals(2, $stats['recent_count']); // today's count
    }

    /** @test */
    public function it_can_get_tindakan_count_attribute()
    {
        $patient = Pasien::factory()->create();
        Tindakan::factory()->count(3)->create(['pasien_id' => $patient->id]);

        $this->assertEquals(3, $patient->tindakan_count);
    }

    /** @test */
    public function it_can_get_last_tindakan_attribute()
    {
        $patient = Pasien::factory()->create();
        $firstTindakan = Tindakan::factory()->create([
            'pasien_id' => $patient->id,
            'created_at' => Carbon::now()->subDays(2),
        ]);
        $lastTindakan = Tindakan::factory()->create([
            'pasien_id' => $patient->id,
            'created_at' => Carbon::now(),
        ]);

        $this->assertEquals($lastTindakan->id, $patient->last_tindakan->id);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $patient = Pasien::factory()->create();
        $patientId = $patient->id;

        $patient->delete();

        $this->assertSoftDeleted('pasien', ['id' => $patientId]);
        $this->assertNull(Pasien::find($patientId));
        $this->assertNotNull(Pasien::withTrashed()->find($patientId));
    }

    /** @test */
    public function it_casts_tanggal_lahir_to_date()
    {
        $patient = Pasien::factory()->create([
            'tanggal_lahir' => '1990-01-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $patient->tanggal_lahir);
        $this->assertEquals('1990-01-01', $patient->tanggal_lahir->format('Y-m-d'));
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Pasien::create([
            'nama' => 'John Doe',
            // Missing no_rekam_medis which might be required
        ]);
    }

    /** @test */
    public function it_can_search_patients_by_name()
    {
        Pasien::factory()->create(['nama' => 'John Doe']);
        Pasien::factory()->create(['nama' => 'Jane Smith']);
        Pasien::factory()->create(['nama' => 'Bob Johnson']);

        $results = Pasien::where('nama', 'LIKE', '%John%')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('nama', 'John Doe'));
        $this->assertTrue($results->contains('nama', 'Bob Johnson'));
    }

    /** @test */
    public function it_can_search_patients_by_no_rekam_medis()
    {
        Pasien::factory()->create(['no_rekam_medis' => 'RM001']);
        Pasien::factory()->create(['no_rekam_medis' => 'RM002']);

        $patient = Pasien::where('no_rekam_medis', 'RM001')->first();

        $this->assertNotNull($patient);
        $this->assertEquals('RM001', $patient->no_rekam_medis);
    }

    /** @test */
    public function it_can_filter_patients_by_date_range()
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now()->subDays(1);

        Pasien::factory()->create(['created_at' => $startDate->addDays(1)]);
        Pasien::factory()->create(['created_at' => $startDate->addDays(3)]);
        Pasien::factory()->create(['created_at' => Carbon::now()->subDays(10)]); // Outside range

        $patients = Pasien::whereBetween('created_at', [$startDate, $endDate])->get();

        $this->assertCount(2, $patients);
    }

    /** @test */
    public function it_can_get_patients_with_tindakan()
    {
        $patientWithTindakan = Pasien::factory()->create();
        $patientWithoutTindakan = Pasien::factory()->create();

        Tindakan::factory()->create(['pasien_id' => $patientWithTindakan->id]);

        $patientsWithTindakan = Pasien::has('tindakan')->get();

        $this->assertCount(1, $patientsWithTindakan);
        $this->assertEquals($patientWithTindakan->id, $patientsWithTindakan->first()->id);
    }

    /** @test */
    public function it_can_count_patients_by_gender()
    {
        Pasien::factory()->count(3)->create(['jenis_kelamin' => 'L']);
        Pasien::factory()->count(2)->create(['jenis_kelamin' => 'P']);

        $maleCount = Pasien::where('jenis_kelamin', 'L')->count();
        $femaleCount = Pasien::where('jenis_kelamin', 'P')->count();

        $this->assertEquals(3, $maleCount);
        $this->assertEquals(2, $femaleCount);
    }

    /** @test */
    public function it_can_get_recent_patients()
    {
        Pasien::factory()->create(['created_at' => Carbon::now()->subDays(5)]);
        Pasien::factory()->create(['created_at' => Carbon::now()->subDays(1)]);
        Pasien::factory()->create(['created_at' => Carbon::now()]);

        $recentPatients = Pasien::where('created_at', '>=', Carbon::now()->subDays(7))
                              ->orderBy('created_at', 'desc')
                              ->get();

        $this->assertCount(3, $recentPatients);
        $this->assertTrue($recentPatients->first()->created_at->isToday());
    }

    /** @test */
    public function it_can_update_patient_information()
    {
        $patient = Pasien::factory()->create([
            'nama' => 'John Doe',
            'alamat' => 'Jakarta',
        ]);

        $patient->update([
            'nama' => 'John Smith',
            'alamat' => 'Bandung',
        ]);

        $this->assertEquals('John Smith', $patient->nama);
        $this->assertEquals('Bandung', $patient->alamat);
        $this->assertDatabaseHas('pasien', [
            'id' => $patient->id,
            'nama' => 'John Smith',
            'alamat' => 'Bandung',
        ]);
    }

    /** @test */
    public function it_logs_activity_when_created()
    {
        $patient = Pasien::factory()->create();

        // Check if activity is logged (assuming LogsActivity trait is working)
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Pasien::class,
            'model_id' => $patient->id,
            'action' => 'created',
        ]);
    }

    /** @test */
    public function it_can_warm_up_cache()
    {
        Pasien::factory()->count(5)->create();

        $cacheResult = Pasien::warmUpCache();

        $this->assertIsArray($cacheResult);
        $this->assertArrayHasKey('total_count', $cacheResult);
        $this->assertArrayHasKey('recent', $cacheResult);
        $this->assertEquals(5, $cacheResult['total_count']);
    }

    /** @test */
    public function it_can_get_cache_statistics()
    {
        $stats = Pasien::getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('model', $stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertEquals('Pasien', $stats['model']);
    }

    /** @test */
    public function it_can_invalidate_model_cache()
    {
        $patient = Pasien::factory()->create();

        $result = $patient->invalidateModelCache();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_cache_sorted_results()
    {
        Pasien::factory()->create(['nama' => 'Alice']);
        Pasien::factory()->create(['nama' => 'Bob']);
        Pasien::factory()->create(['nama' => 'Charlie']);

        $sortedPatients = Pasien::cacheSorted('by_name', 'nama', 'asc');

        $this->assertCount(3, $sortedPatients);
        $this->assertEquals('Alice', $sortedPatients->first()->nama);
        $this->assertEquals('Charlie', $sortedPatients->last()->nama);
    }
}