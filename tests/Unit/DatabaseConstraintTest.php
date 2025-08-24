<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\JenisTindakan;
use App\Models\Tindakan;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\Pasien;
use App\Models\User;

class DatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that JenisTindakan factory creates valid records with correct enum values
     */
    public function test_jenis_tindakan_factory_creates_valid_records()
    {
        $jenisTindakan = JenisTindakan::factory()->create();

        $this->assertDatabaseHas('jenis_tindakan', [
            'id' => $jenisTindakan->id,
        ]);

        // Check that kategori is one of the valid enum values
        $validKategori = ['konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya'];
        $this->assertContains($jenisTindakan->kategori, $validKategori);
    }

    /**
     * Test that Tindakan factory creates valid records with all required foreign keys
     */
    public function test_tindakan_factory_creates_valid_records()
    {
        $tindakan = Tindakan::factory()->create();

        $this->assertDatabaseHas('tindakan', [
            'id' => $tindakan->id,
        ]);

        // Check that all required fields are not null
        $this->assertNotNull($tindakan->pasien_id);
        $this->assertNotNull($tindakan->jenis_tindakan_id);
        $this->assertNotNull($tindakan->shift_id);
        $this->assertNotNull($tindakan->input_by);

        // Check that foreign key relationships exist
        $this->assertInstanceOf(Pasien::class, $tindakan->pasien);
        $this->assertInstanceOf(JenisTindakan::class, $tindakan->jenisTindakan);
        $this->assertInstanceOf(Shift::class, $tindakan->shift);
        $this->assertInstanceOf(User::class, $tindakan->inputBy);

        // Check dokter relationship (can be null but if present should be valid)
        if ($tindakan->dokter_id) {
            $this->assertInstanceOf(Dokter::class, $tindakan->dokter);
        }

        // Check pegawai relationships (can be null but if present should be valid)
        if ($tindakan->paramedis_id) {
            $this->assertInstanceOf(Pegawai::class, $tindakan->paramedis);
        }

        if ($tindakan->non_paramedis_id) {
            $this->assertInstanceOf(Pegawai::class, $tindakan->nonParamedis);
        }
    }

    /**
     * Test that DokterFactory creates valid records
     */
    public function test_dokter_factory_creates_valid_records()
    {
        $dokter = Dokter::factory()->create();

        $this->assertDatabaseHas('dokters', [
            'id' => $dokter->id,
        ]);

        $this->assertNotNull($dokter->nama_lengkap);
        $this->assertNotNull($dokter->jabatan);
    }

    /**
     * Test creating multiple Tindakan records doesn't cause constraint violations
     */
    public function test_multiple_tindakan_creation_no_constraint_violations()
    {
        // Create multiple tindakan using factories
        $tindakanList = Tindakan::factory()->count(5)->create();

        $this->assertCount(5, $tindakanList);

        foreach ($tindakanList as $tindakan) {
            $this->assertDatabaseHas('tindakan', [
                'id' => $tindakan->id,
            ]);
        }
    }
}