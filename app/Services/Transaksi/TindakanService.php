<?php

namespace App\Services\Transaksi;

use App\Events\DataInputDisimpan;
use App\Models\JenisTindakan;
use App\Models\Tindakan;
use App\Repositories\TindakanRepository;
use App\Services\Jaspel\JaspelService;
use Illuminate\Support\Facades\DB;

class TindakanService
{
    protected $tindakanRepository;
    protected $jaspelService;

    public function __construct(
        TindakanRepository $tindakanRepository,
        JaspelService $jaspelService
    ) {
        $this->tindakanRepository = $tindakanRepository;
        $this->jaspelService = $jaspelService;
    }

    public function create(array $data): Tindakan
    {
        return DB::transaction(function () use ($data) {
            $jenisTindakan = JenisTindakan::find($data['jenis_tindakan_id']);
            
            $tindakanData = array_merge($data, [
                'tarif' => $jenisTindakan->tarif,
                'jasa_dokter' => $jenisTindakan->jasa_dokter,
                'jasa_paramedis' => $jenisTindakan->jasa_paramedis,
                'jasa_non_paramedis' => $jenisTindakan->jasa_non_paramedis,
                'status' => 'pending',
            ]);

            $tindakan = $this->tindakanRepository->create($tindakanData);
            
            // Trigger event for data input
            event(new DataInputDisimpan($tindakan, auth()->user()));
            
            return $tindakan;
        });
    }

    public function update(Tindakan $tindakan, array $data): Tindakan
    {
        return DB::transaction(function () use ($tindakan, $data) {
            if (isset($data['jenis_tindakan_id']) && $data['jenis_tindakan_id'] !== $tindakan->jenis_tindakan_id) {
                $jenisTindakan = JenisTindakan::find($data['jenis_tindakan_id']);
                $data['tarif'] = $jenisTindakan->tarif;
                $data['jasa_dokter'] = $jenisTindakan->jasa_dokter;
                $data['jasa_paramedis'] = $jenisTindakan->jasa_paramedis;
                $data['jasa_non_paramedis'] = $jenisTindakan->jasa_non_paramedis;
            }

            return $this->tindakanRepository->update($tindakan, $data);
        });
    }

    public function delete(Tindakan $tindakan): bool
    {
        return DB::transaction(function () use ($tindakan) {
            // Delete related jaspel records
            $tindakan->jaspel()->delete();
            
            return $this->tindakanRepository->delete($tindakan);
        });
    }

    public function complete(Tindakan $tindakan): Tindakan
    {
        return DB::transaction(function () use ($tindakan) {
            $tindakan = $this->tindakanRepository->update($tindakan, [
                'status' => 'selesai'
            ]);
            
            // Generate jaspel automatically
            $this->jaspelService->generateFromTindakan($tindakan->id);
            
            return $tindakan;
        });
    }

    public function cancel(Tindakan $tindakan): Tindakan
    {
        return DB::transaction(function () use ($tindakan) {
            // Cancel related jaspel records
            $tindakan->jaspel()->update(['status_validasi' => 'ditolak']);
            
            return $this->tindakanRepository->update($tindakan, [
                'status' => 'batal'
            ]);
        });
    }

    public function getStatistics(array $filters = []): array
    {
        return $this->tindakanRepository->getStatistics($filters);
    }

    public function getRevenueByPeriod(string $startDate, string $endDate): array
    {
        return $this->tindakanRepository->getRevenueByPeriod($startDate, $endDate);
    }
}