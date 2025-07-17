<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class JenisTindakanResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'nama_tindakan' => $this->nama_tindakan,
            'kategori' => $this->kategori,
            'tarif_dasar' => $this->tarif_dasar,
            'deskripsi' => $this->deskripsi,
            'is_active' => $this->is_active ?? true,
            
            // Formatted fields
            'formatted_tarif' => $this->formatCurrency($this->tarif_dasar),
            'status_label' => ($this->is_active ?? true) ? 'Aktif' : 'Tidak Aktif',
        ]);
    }

    /**
     * Transform for minimal view
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_tindakan' => $this->nama_tindakan,
            'kategori' => $this->kategori,
            'tarif_dasar' => $this->tarif_dasar,
            'formatted_tarif' => $this->formatCurrency($this->tarif_dasar),
        ];
    }
}