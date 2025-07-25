<?php

namespace App\Jobs;

use App\Models\JumlahPasienHarian;
use App\Models\DokterUmumJaspel;
use App\Models\Jaspel;
use App\Models\Shift;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HitungJaspelPasienJob implements ShouldQueue
{
    use Queueable;

    public int $pasienHarianId;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $pasienHarianId)
    {
        $this->pasienHarianId = $pasienHarianId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $pasienHarian = JumlahPasienHarian::find($this->pasienHarianId);
            
            if (!$pasienHarian) {
                Log::warning("JumlahPasienHarian dengan ID {$this->pasienHarianId} tidak ditemukan");
                return;
            }

            if (!$pasienHarian->isApproved()) {
                Log::info("JumlahPasienHarian ID {$this->pasienHarianId} belum disetujui, skip hitung jaspel");
                return;
            }

            // Check if jaspel already exists for this date and doctor
            $existingJaspel = Jaspel::where('user_id', $pasienHarian->dokter->user_id)
                ->whereDate('tanggal', $pasienHarian->tanggal)
                ->where('jenis_jaspel', 'pasien_harian')
                ->exists();

            if ($existingJaspel) {
                Log::info("Jaspel pasien harian untuk dokter ID {$pasienHarian->dokter->user_id} tanggal {$pasienHarian->tanggal} sudah ada");
                return;
            }

            // Get shift information based on current time or set default
            $shift = Shift::where('nama', 'Pagi')->first(); // Default shift
            
            // Calculate jaspel based on patient count and formula
            $totalPasien = $pasienHarian->total_pasien;
            $jaspelFormula = $this->getJaspelFormula($pasienHarian->poli, $shift->id ?? 1);
            
            $nominalJaspel = $this->calculateJaspel($totalPasien, $jaspelFormula);

            if ($nominalJaspel > 0) {
                // Create jaspel record
                Jaspel::create([
                    'user_id' => $pasienHarian->dokter->user_id,
                    'tanggal' => $pasienHarian->tanggal,
                    'shift_id' => $shift->id ?? 1,
                    'jenis_jaspel' => 'pasien_harian',
                    'nominal' => $nominalJaspel,
                    'keterangan' => "Jaspel {$pasienHarian->poli} - {$totalPasien} pasien (Umum: {$pasienHarian->jumlah_pasien_umum}, BPJS: {$pasienHarian->jumlah_pasien_bpjs})",
                    'status_validasi' => 'approved', // Auto approve jaspel dari validasi bendahara
                    'input_by' => $pasienHarian->validasi_by,
                    'validasi_by' => $pasienHarian->validasi_by,
                    'validasi_at' => now(),
                ]);

                Log::info("Berhasil menghitung jaspel pasien harian untuk dokter ID {$pasienHarian->dokter->user_id}", [
                    'tanggal' => $pasienHarian->tanggal,
                    'total_pasien' => $totalPasien,
                    'nominal_jaspel' => $nominalJaspel
                ]);
            }

        } catch (Exception $e) {
            Log::error("Gagal menghitung jaspel pasien harian untuk ID {$this->pasienHarianId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get jaspel formula based on poli and shift
     */
    private function getJaspelFormula(string $poli, int $shiftId): ?DokterUmumJaspel
    {
        // Map poli to dokter type
        $jenisLayanan = match ($poli) {
            'umum' => 'umum',
            'gigi' => 'gigi',
            default => 'umum'
        };

        return DokterUmumJaspel::where('shift_id', $shiftId)
            ->where('jenis_layanan', $jenisLayanan)
            ->where('is_active', true)
            ->orderBy('threshold_pasien', 'desc')
            ->first();
    }

    /**
     * Calculate jaspel based on patient count and formula
     */
    private function calculateJaspel(int $totalPasien, ?DokterUmumJaspel $formula): float
    {
        if (!$formula) {
            Log::warning("Formula jaspel tidak ditemukan untuk jumlah pasien: {$totalPasien}");
            return 0;
        }

        // Check if patient count meets threshold
        if ($totalPasien < $formula->threshold_pasien) {
            Log::info("Jumlah pasien ({$totalPasien}) belum mencapai threshold ({$formula->threshold_pasien})");
            return 0;
        }

        // Calculate based on formula type
        if ($formula->tipe_perhitungan === 'fixed') {
            return $formula->nominal_jaspel;
        } elseif ($formula->tipe_perhitungan === 'per_pasien') {
            return $totalPasien * $formula->nominal_jaspel;
        } elseif ($formula->tipe_perhitungan === 'progressive') {
            // Progressive calculation: base amount + (excess patients * per patient rate)
            $excessPatients = $totalPasien - $formula->threshold_pasien;
            return $formula->nominal_jaspel + ($excessPatients * ($formula->multiplier ?? 0));
        }

        return $formula->nominal_jaspel;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("HitungJaspelPasienJob gagal untuk pasien harian ID {$this->pasienHarianId}: " . $exception->getMessage());
    }
}