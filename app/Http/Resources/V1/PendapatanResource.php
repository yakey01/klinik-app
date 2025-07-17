<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class PendapatanResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'tanggal_pendapatan' => $this->tanggal_pendapatan,
            'sumber_pendapatan' => $this->sumber_pendapatan,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'tindakan_id' => $this->tindakan_id,
            'input_by' => $this->input_by,
            
            // Formatted fields
            'formatted_date' => $this->formatDate($this->tanggal_pendapatan),
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'category' => $this->categorizeRevenue(),
            'created_ago' => $this->timeAgo($this->created_at),
            
            // Relationships
            'tindakan' => new TindakanResource($this->whenLoaded('tindakan')),
            'input_by_user' => new UserResource($this->whenLoaded('inputBy')),
            
            // Related patient info (through tindakan)
            'patient_info' => $this->when(
                $this->relationLoaded('tindakan'),
                [
                    'id' => $this->safeGet('tindakan.pasien.id'),
                    'name' => $this->safeGet('tindakan.pasien.nama_pasien'),
                    'number' => $this->safeGet('tindakan.pasien.nomor_pasien'),
                ]
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
            'tanggal_pendapatan' => $this->tanggal_pendapatan,
            'formatted_date' => $this->formatDate($this->tanggal_pendapatan),
            'sumber_pendapatan' => $this->sumber_pendapatan,
            'jumlah' => $this->jumlah,
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'category' => $this->categorizeRevenue(),
            'patient_name' => $this->safeGet('tindakan.pasien.nama_pasien'),
            'procedure_type' => $this->safeGet('tindakan.jenisTindakan.nama_tindakan'),
        ];
    }

    /**
     * Transform for mobile app (optimized)
     */
    public function toArrayMobile(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->tanggal_pendapatan,
            'formatted_date' => $this->formatDate($this->tanggal_pendapatan),
            'source' => $this->sumber_pendapatan,
            'amount' => $this->jumlah,
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'category' => $this->categorizeRevenue(),
            'description' => $this->keterangan,
            'patient' => $this->safeGet('tindakan.pasien.nama_pasien'),
        ];
    }

    /**
     * Transform for financial dashboard
     */
    public function toArrayDashboard(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->jumlah,
            'date' => $this->tanggal_pendapatan,
            'category' => $this->categorizeRevenue(),
            'source' => $this->sumber_pendapatan,
            'month' => \Carbon\Carbon::parse($this->tanggal_pendapatan)->format('Y-m'),
            'week' => \Carbon\Carbon::parse($this->tanggal_pendapatan)->weekOfYear,
        ];
    }

    /**
     * Categorize revenue source
     */
    protected function categorizeRevenue(): string
    {
        $sumber = strtolower($this->sumber_pendapatan);
        
        if (str_contains($sumber, 'konsultasi') || str_contains($sumber, 'konsul')) {
            return 'consultation';
        } elseif (str_contains($sumber, 'obat') || str_contains($sumber, 'farmasi')) {
            return 'medication';
        } elseif (str_contains($sumber, 'alat') || str_contains($sumber, 'peralatan')) {
            return 'equipment';
        } elseif (str_contains($sumber, 'tindakan') || str_contains($sumber, 'medis') || str_contains($sumber, 'operasi')) {
            return 'procedure';
        }
        
        return 'other';
    }
}