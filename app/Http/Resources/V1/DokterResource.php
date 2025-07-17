<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class DokterResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'nama_dokter' => $this->nama_dokter,
            'spesialisasi' => $this->spesialisasi,
            'nomor_sip' => $this->nomor_sip,
            'jabatan' => $this->jabatan,
            'nomor_telepon' => $this->nomor_telepon,
            'email' => $this->email,
            'alamat' => $this->alamat,
            
            // Formatted fields
            'jabatan_label' => $this->getJabatanLabel(),
            'contact_info' => $this->getContactInfo(),
        ]);
    }

    /**
     * Transform for minimal view
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_dokter' => $this->nama_dokter,
            'jabatan' => $this->jabatan,
            'jabatan_label' => $this->getJabatanLabel(),
            'spesialisasi' => $this->spesialisasi,
        ];
    }

    /**
     * Get jabatan label
     */
    protected function getJabatanLabel(): string
    {
        return match ($this->jabatan) {
            'dokter_umum' => 'Dokter Umum',
            'dokter_gigi' => 'Dokter Gigi',
            'dokter_spesialis' => 'Dokter Spesialis',
            default => $this->jabatan,
        };
    }

    /**
     * Get contact information
     */
    protected function getContactInfo(): array
    {
        return array_filter([
            'phone' => $this->nomor_telepon,
            'email' => $this->email,
        ]);
    }
}