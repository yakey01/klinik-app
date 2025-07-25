<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class TindakanResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'tanggal_tindakan' => $this->tanggal_tindakan,
            'keluhan' => $this->keluhan,
            'diagnosa' => $this->diagnosa,
            'tindakan_medis' => $this->tindakan_medis,
            'obat_diberikan' => $this->obat_diberikan,
            'catatan_tambahan' => $this->catatan_tambahan,
            'tarif' => $this->tarif,
            'status_validasi' => $this->status_validasi,
            'catatan_validasi' => $this->catatan_validasi,
            'dokter_id' => $this->dokter_id,
            'paramedis_id' => $this->paramedis_id,
            'non_paramedis_id' => $this->non_paramedis_id,
            'pasien_id' => $this->pasien_id,
            'jenis_tindakan_id' => $this->jenis_tindakan_id,
            
            // Formatted fields
            'formatted_date' => $this->formatDate($this->tanggal_tindakan),
            'formatted_tarif' => $this->formatCurrency($this->tarif),
            'status_label' => $this->getStatusLabel(),
            'priority' => $this->getPriority(),
            'created_ago' => $this->timeAgo($this->created_at),
            'procedure_time' => $this->formatDateTime($this->tanggal_tindakan),
            
            // Relationships
            'pasien' => new PasienResource($this->whenLoaded('pasien')),
            'dokter' => new DokterResource($this->whenLoaded('dokter')),
            'jenis_tindakan' => new JenisTindakanResource($this->whenLoaded('jenisTindakan')),
            'paramedis' => new PegawaiResource($this->whenLoaded('paramedis')),
            'non_paramedis' => new PegawaiResource($this->whenLoaded('nonParamedis')),
            'pendapatan' => new PendapatanResource($this->whenLoaded('pendapatan')),
            
            // Team information
            'team' => $this->when(
                $this->relationLoaded('dokter') || $this->relationLoaded('paramedis') || $this->relationLoaded('nonParamedis'),
                $this->getTeamInfo()
            ),
        ]);
    }

    /**
     * Transform for list view (minimal data)
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal_tindakan' => $this->tanggal_tindakan,
            'formatted_date' => $this->formatDate($this->tanggal_tindakan),
            'keluhan' => $this->keluhan,
            'diagnosa' => $this->diagnosa,
            'tarif' => $this->tarif,
            'formatted_tarif' => $this->formatCurrency($this->tarif),
            'status_validasi' => $this->status_validasi,
            'status_label' => $this->getStatusLabel(),
            'pasien_nama' => $this->safeGet('pasien.nama_pasien'),
            'jenis_tindakan' => $this->safeGet('jenisTindakan.nama_tindakan'),
            'dokter_nama' => $this->safeGet('dokter.nama_dokter'),
        ];
    }

    /**
     * Transform for mobile app (optimized)
     */
    public function toArrayMobile(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->tanggal_tindakan,
            'formatted_date' => $this->formatDate($this->tanggal_tindakan),
            'complaint' => $this->keluhan,
            'diagnosis' => $this->diagnosa,
            'treatment' => $this->tindakan_medis,
            'fee' => $this->tarif,
            'formatted_fee' => $this->formatCurrency($this->tarif),
            'status' => $this->status_validasi,
            'status_label' => $this->getStatusLabel(),
            'patient' => [
                'id' => $this->safeGet('pasien.id'),
                'name' => $this->safeGet('pasien.nama_pasien'),
                'number' => $this->safeGet('pasien.nomor_pasien'),
            ],
            'procedure_type' => $this->safeGet('jenisTindakan.nama_tindakan'),
            'doctor' => $this->safeGet('dokter.nama_dokter'),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status_validasi) {
            'pending' => 'Menunggu Validasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Get priority based on status and date
     */
    protected function getPriority(): string
    {
        if ($this->status_validasi === 'pending') {
            $daysDiff = \Carbon\Carbon::parse($this->tanggal_tindakan)->diffInDays(now());
            if ($daysDiff > 7) return 'high';
            if ($daysDiff > 3) return 'medium';
        }
        return 'normal';
    }

    /**
     * Get team information
     */
    protected function getTeamInfo(): array
    {
        $team = [];
        
        if ($this->relationLoaded('dokter') && $this->dokter) {
            $team['dokter'] = [
                'id' => $this->dokter->id,
                'nama' => $this->dokter->nama_dokter,
                'jabatan' => $this->dokter->jabatan,
            ];
        }
        
        if ($this->relationLoaded('paramedis') && $this->paramedis) {
            $team['paramedis'] = [
                'id' => $this->paramedis->id,
                'nama' => $this->paramedis->nama_pegawai,
            ];
        }
        
        if ($this->relationLoaded('nonParamedis') && $this->nonParamedis) {
            $team['non_paramedis'] = [
                'id' => $this->nonParamedis->id,
                'nama' => $this->nonParamedis->nama_pegawai,
            ];
        }
        
        return $team;
    }
}