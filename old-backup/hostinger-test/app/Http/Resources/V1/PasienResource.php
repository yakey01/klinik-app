<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class PasienResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'nomor_pasien' => $this->nomor_pasien,
            'nama_pasien' => $this->nama_pasien,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'alamat' => $this->alamat,
            'nomor_telepon' => $this->nomor_telepon,
            'email' => $this->email,
            'pekerjaan' => $this->pekerjaan,
            'status_pernikahan' => $this->status_pernikahan,
            'golongan_darah' => $this->golongan_darah,
            'alergi' => $this->alergi,
            'kontak_darurat_nama' => $this->kontak_darurat_nama,
            'kontak_darurat_hubungan' => $this->kontak_darurat_hubungan,
            'kontak_darurat_telepon' => $this->kontak_darurat_telepon,
            'catatan_medis' => $this->catatan_medis,
            
            // Formatted fields
            'formatted_birth_date' => $this->formatDate($this->tanggal_lahir),
            'age' => $this->tanggal_lahir ? \Carbon\Carbon::parse($this->tanggal_lahir)->age : null,
            'gender_label' => $this->jenis_kelamin === 'L' ? 'Laki-laki' : ($this->jenis_kelamin === 'P' ? 'Perempuan' : null),
            'created_ago' => $this->timeAgo($this->created_at),
            'updated_ago' => $this->timeAgo($this->updated_at),
            
            // Relationships
            'recent_procedures' => TindakanResource::collection($this->whenLoaded('tindakans')),
            'total_procedures' => $this->when(
                $this->relationLoaded('tindakans'),
                $this->tindakans?->count() ?? 0
            ),
            'last_visit' => $this->when(
                $this->relationLoaded('tindakans'),
                $this->formatDate($this->tindakans?->max('tanggal_tindakan'))
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
            'nomor_pasien' => $this->nomor_pasien,
            'nama_pasien' => $this->nama_pasien,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'nomor_telepon' => $this->nomor_telepon,
            'formatted_birth_date' => $this->formatDate($this->tanggal_lahir),
            'age' => $this->tanggal_lahir ? \Carbon\Carbon::parse($this->tanggal_lahir)->age : null,
            'gender_label' => $this->jenis_kelamin === 'L' ? 'Laki-laki' : ($this->jenis_kelamin === 'P' ? 'Perempuan' : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Transform for mobile app (optimized)
     */
    public function toArrayMobile(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_pasien' => $this->nomor_pasien,
            'nama_pasien' => $this->nama_pasien,
            'age' => $this->tanggal_lahir ? \Carbon\Carbon::parse($this->tanggal_lahir)->age : null,
            'gender' => $this->jenis_kelamin,
            'gender_label' => $this->jenis_kelamin === 'L' ? 'Laki-laki' : ($this->jenis_kelamin === 'P' ? 'Perempuan' : null),
            'phone' => $this->nomor_telepon,
            'last_visit' => $this->when(
                $this->relationLoaded('tindakans'),
                $this->formatDate($this->tindakans?->max('tanggal_tindakan'))
            ),
            'total_visits' => $this->when(
                $this->relationLoaded('tindakans'),
                $this->tindakans?->count() ?? 0
            ),
        ];
    }
}