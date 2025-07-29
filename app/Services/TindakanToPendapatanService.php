<?php

namespace App\Services;

use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\Pendapatan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TindakanToPendapatanService
{
    /**
     * Create pendapatan harian records from completed tindakan
     */
    public function createPendapatanFromTindakan(Tindakan $tindakan, ?User $createdBy = null): array
    {
        return DB::transaction(function () use ($tindakan, $createdBy) {
            $results = [];
            $createdBy = $createdBy ?? auth()->user() ?? User::find(1);

            // Check if tindakan is completed and approved
            if ($tindakan->status !== 'selesai' || $tindakan->status_validasi !== 'disetujui') {
                throw new \Exception('Tindakan must be completed and approved before creating income records');
            }

            // Check if pendapatan records already exist for this tindakan
            $existingRecords = PendapatanHarian::where('tindakan_id', $tindakan->id)->count();
            if ($existingRecords > 0) {
                throw new \Exception('Income records already exist for this tindakan');
            }

            // Get or create pendapatan master data for tindakan-based income
            $pendapatanMaster = $this->getOrCreateTindakanPendapatanMaster($tindakan);

            // Create clinic revenue (tarif - jaspel)
            $clinicRevenue = $this->createClinicRevenue($tindakan, $pendapatanMaster, $createdBy);
            $results[] = $clinicRevenue;

            // Create jaspel records for performers
            $jaspelRecords = $this->createJaspelRecords($tindakan, $pendapatanMaster, $createdBy);
            $results = array_merge($results, $jaspelRecords);

            return [
                'success' => true,
                'message' => 'Successfully created ' . count($results) . ' income records from tindakan',
                'records' => $results,
                'tindakan_id' => $tindakan->id,
            ];
        });
    }

    /**
     * Get or create pendapatan master for tindakan-based income
     */
    private function getOrCreateTindakanPendapatanMaster(Tindakan $tindakan): Pendapatan
    {
        // Check if specific pendapatan exists for this jenis tindakan
        $pendapatanName = "Pendapatan dari {$tindakan->jenisTindakan->nama}";
        
        $pendapatan = Pendapatan::where('nama_pendapatan', $pendapatanName)->first();
        
        if (!$pendapatan) {
            $pendapatan = Pendapatan::create([
                'nama_pendapatan' => $pendapatanName,
                'kategori' => 'Tindakan Medis',
                'is_aktif' => true,
                'tanggal' => $tindakan->tanggal_tindakan->toDateString(),
                'nominal' => 0, // This is master data, not the actual amount
                'sumber_pendapatan' => 'Tindakan Medis',
                'kode_pendapatan' => 'TDK-' . $tindakan->jenis_tindakan_id,
                'tindakan_id' => $tindakan->id,
                'input_by' => auth()->id() ?? 1,
                'status_validasi' => 'disetujui', // Master data is pre-approved
                'keterangan' => "Auto-generated from tindakan: {$tindakan->jenisTindakan->nama}",
            ]);
        }

        return $pendapatan;
    }

    /**
     * Create clinic revenue record (tarif - total jaspel)
     */
    private function createClinicRevenue(Tindakan $tindakan, Pendapatan $pendapatanMaster, User $createdBy): PendapatanHarian
    {
        $totalJaspel = ($tindakan->jasa_dokter ?? 0) + ($tindakan->jasa_paramedis ?? 0) + ($tindakan->jasa_non_paramedis ?? 0);
        $clinicRevenue = $tindakan->tarif - $totalJaspel;

        return PendapatanHarian::create([
            'tanggal_input' => $tindakan->tanggal_tindakan->toDateString(),
            'shift' => $tindakan->shift->nama_shift ?? 'Unknown',
            'pendapatan_id' => $pendapatanMaster->id,
            'tindakan_id' => $tindakan->id,
            'source_type' => 'tindakan',
            'tindakan_code' => $tindakan->jenisTindakan->kode ?? 'TDK-' . $tindakan->id,
            'tindakan_tarif' => $tindakan->tarif,
            'jaspel_amount' => 0, // This is clinic revenue, not jaspel
            'performer_type' => 'clinic',
            'performer_id' => null,
            'nominal' => $clinicRevenue,
            'deskripsi' => "Pendapatan klinik dari tindakan: {$tindakan->jenisTindakan->nama} - Pasien: {$tindakan->pasien->nama}",
            'user_id' => $createdBy->id,
            'status_validasi' => 'pending',
        ]);
    }

    /**
     * Create jaspel records for all performers involved in the tindakan
     */
    private function createJaspelRecords(Tindakan $tindakan, Pendapatan $pendapatanMaster, User $createdBy): array
    {
        $jaspelRecords = [];

        // Create jaspel record for dokter if involved
        if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
            $jaspelRecords[] = $this->createJaspelRecord(
                $tindakan,
                $pendapatanMaster,
                $createdBy,
                'dokter',
                $tindakan->dokter_id,
                $tindakan->jasa_dokter,
                $tindakan->dokter->nama_lengkap ?? 'Unknown Doctor'
            );
        }

        // Create jaspel record for paramedis if involved
        if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
            $jaspelRecords[] = $this->createJaspelRecord(
                $tindakan,
                $pendapatanMaster,
                $createdBy,
                'paramedis',
                $tindakan->paramedis_id,
                $tindakan->jasa_paramedis,
                $tindakan->paramedis->nama_lengkap ?? 'Unknown Paramedic'
            );
        }

        // Create jaspel record for non-paramedis if involved
        if ($tindakan->non_paramedis_id && $tindakan->jasa_non_paramedis > 0) {
            $jaspelRecords[] = $this->createJaspelRecord(
                $tindakan,
                $pendapatanMaster,
                $createdBy,
                'non_paramedis',
                $tindakan->non_paramedis_id,
                $tindakan->jasa_non_paramedis,
                $tindakan->nonParamedis->nama_lengkap ?? 'Unknown Non-Paramedic'
            );
        }

        return $jaspelRecords;
    }

    /**
     * Create individual jaspel record
     */
    private function createJaspelRecord(
        Tindakan $tindakan,
        Pendapatan $pendapatanMaster,
        User $createdBy,
        string $performerType,
        int $performerId,
        float $jaspelAmount,
        string $performerName
    ): PendapatanHarian {
        return PendapatanHarian::create([
            'tanggal_input' => $tindakan->tanggal_tindakan->toDateString(),
            'shift' => $tindakan->shift->nama_shift ?? 'Unknown',
            'pendapatan_id' => $pendapatanMaster->id,
            'tindakan_id' => $tindakan->id,
            'source_type' => 'tindakan',
            'tindakan_code' => $tindakan->jenisTindakan->kode ?? 'TDK-' . $tindakan->id,
            'tindakan_tarif' => $tindakan->tarif,
            'jaspel_amount' => $jaspelAmount,
            'performer_type' => $performerType,
            'performer_id' => $performerId,
            'nominal' => $jaspelAmount,
            'deskripsi' => "Jaspel {$performerType} ({$performerName}) dari tindakan: {$tindakan->jenisTindakan->nama} - Pasien: {$tindakan->pasien->nama}",
            'user_id' => $createdBy->id,
            'status_validasi' => 'pending',
        ]);
    }

    /**
     * Get available completed tindakan that haven't been converted to pendapatan yet
     */
    public function getAvailableTindakanForConversion(?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Tindakan::with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift'])
            ->where('status', 'selesai')
            ->where('status_validasi', 'disetujui')
            ->whereDoesntHave('pendapatanHarian'); // Only tindakan without existing pendapatan records

        if ($user) {
            $query->where('input_by', $user->id);
        }

        return $query->orderBy('tanggal_tindakan', 'desc')->get();
    }

    /**
     * Bulk convert multiple tindakan to pendapatan
     */
    public function bulkCreatePendapatanFromTindakan(array $tindakanIds, ?User $createdBy = null): array
    {
        $results = [];
        $errors = [];
        $createdBy = $createdBy ?? auth()->user() ?? User::find(1);

        foreach ($tindakanIds as $tindakanId) {
            try {
                $tindakan = Tindakan::findOrFail($tindakanId);
                $result = $this->createPendapatanFromTindakan($tindakan, $createdBy);
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'tindakan_id' => $tindakanId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => count($results),
            'errors' => count($errors),
            'results' => $results,
            'error_details' => $errors,
        ];
    }

    /**
     * Delete pendapatan records associated with a tindakan (for reversal)
     */
    public function deletePendapatanFromTindakan(Tindakan $tindakan): array
    {
        return DB::transaction(function () use ($tindakan) {
            $pendapatanRecords = PendapatanHarian::where('tindakan_id', $tindakan->id)->get();
            
            if ($pendapatanRecords->isEmpty()) {
                throw new \Exception('No income records found for this tindakan');
            }

            // Check if any records are already validated
            $validatedRecords = $pendapatanRecords->where('status_validasi', 'disetujui');
            if ($validatedRecords->isNotEmpty()) {
                throw new \Exception('Cannot delete validated income records. Please contact administrator.');
            }

            $deletedCount = $pendapatanRecords->count();
            
            // Delete all related pendapatan records
            foreach ($pendapatanRecords as $record) {
                $record->delete();
            }

            return [
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} income records",
                'deleted_count' => $deletedCount,
            ];
        });
    }

}