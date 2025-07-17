<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;

class PengeluaranResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'nama_pengeluaran' => $this->nama_pengeluaran,
            'kategori' => $this->kategori,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'priority' => $this->priority ?? 'medium',
            'status' => $this->status ?? 'pending',
            'input_by' => $this->input_by,
            
            // Formatted fields
            'formatted_date' => $this->formatDate($this->tanggal_pengeluaran),
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'priority_label' => $this->getPriorityLabel(),
            'status_label' => $this->getStatusLabel(),
            'created_ago' => $this->timeAgo($this->created_at),
            
            // Budget information
            'budget_info' => $this->when(
                request()->has('include_budget'),
                $this->getBudgetInfo()
            ),
            
            // Relationships
            'input_by_user' => new UserResource($this->whenLoaded('inputBy')),
        ]);
    }

    /**
     * Transform for list view (minimal data)
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'formatted_date' => $this->formatDate($this->tanggal_pengeluaran),
            'nama_pengeluaran' => $this->nama_pengeluaran,
            'kategori' => $this->kategori,
            'jumlah' => $this->jumlah,
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'priority' => $this->priority ?? 'medium',
            'status' => $this->status ?? 'pending',
            'priority_label' => $this->getPriorityLabel(),
            'status_label' => $this->getStatusLabel(),
        ];
    }

    /**
     * Transform for mobile app (optimized)
     */
    public function toArrayMobile(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->tanggal_pengeluaran,
            'formatted_date' => $this->formatDate($this->tanggal_pengeluaran),
            'name' => $this->nama_pengeluaran,
            'category' => $this->kategori,
            'amount' => $this->jumlah,
            'formatted_amount' => $this->formatCurrency($this->jumlah),
            'priority' => $this->priority ?? 'medium',
            'status' => $this->status ?? 'pending',
            'description' => $this->keterangan,
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
            'date' => $this->tanggal_pengeluaran,
            'category' => $this->kategori,
            'priority' => $this->priority ?? 'medium',
            'status' => $this->status ?? 'pending',
            'month' => \Carbon\Carbon::parse($this->tanggal_pengeluaran)->format('Y-m'),
            'week' => \Carbon\Carbon::parse($this->tanggal_pengeluaran)->weekOfYear,
        ];
    }

    /**
     * Get priority label
     */
    protected function getPriorityLabel(): string
    {
        return match ($this->priority ?? 'medium') {
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
            default => 'Sedang',
        };
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status ?? 'pending') {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'paid' => 'Dibayar',
            default => 'Menunggu',
        };
    }

    /**
     * Get budget information for this expense
     */
    protected function getBudgetInfo(): array
    {
        // This would integrate with the budget system
        // For now, return basic structure
        return [
            'category_limit' => 0,
            'category_spent' => 0,
            'category_remaining' => 0,
            'utilization_percentage' => 0,
            'is_over_budget' => false,
            'warning_threshold' => 80,
        ];
    }
}