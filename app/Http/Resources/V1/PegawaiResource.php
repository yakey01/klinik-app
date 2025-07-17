<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class PegawaiResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'nama_pegawai' => $this->nama_pegawai,
            'jenis_pegawai' => $this->jenis_pegawai,
            'nomor_telepon' => $this->nomor_telepon,
            'email' => $this->email,
            'alamat' => $this->alamat,
            'tanggal_masuk' => $this->tanggal_masuk,
            'status_aktif' => $this->status_aktif,
            
            // Formatted fields
            'jenis_pegawai_label' => $this->getJenisPegawaiLabel(),
            'formatted_join_date' => $this->formatDate($this->tanggal_masuk),
            'status_label' => $this->status_aktif ? 'Aktif' : 'Tidak Aktif',
            'work_duration' => $this->getWorkDuration(),
        ]);
    }

    /**
     * Transform for minimal view
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_pegawai' => $this->nama_pegawai,
            'jenis_pegawai' => $this->jenis_pegawai,
            'jenis_pegawai_label' => $this->getJenisPegawaiLabel(),
            'status_aktif' => $this->status_aktif,
        ];
    }

    /**
     * Get jenis pegawai label
     */
    protected function getJenisPegawaiLabel(): string
    {
        return match ($this->jenis_pegawai) {
            'paramedis' => 'Paramedis',
            'non_paramedis' => 'Non-Paramedis',
            default => $this->jenis_pegawai,
        };
    }

    /**
     * Get work duration
     */
    protected function getWorkDuration(): ?string
    {
        if (!$this->tanggal_masuk) {
            return null;
        }

        return \Carbon\Carbon::parse($this->tanggal_masuk)->diffForHumans(null, true);
    }
}