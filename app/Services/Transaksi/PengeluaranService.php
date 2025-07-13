<?php

namespace App\Services\Transaksi;

use App\Events\DataInputDisimpan;
use App\Events\ExpenseCreated;
use App\Models\Pengeluaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PengeluaranService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Pengeluaran::with(['inputBy', 'validasiBy'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['kategori'] ?? null, function ($query, $kategori) {
                $query->byKategori($kategori);
            })
            ->when($filters['status_validasi'] ?? null, function ($query, $status) {
                $query->byStatus($status);
            });

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Pengeluaran
    {
        return DB::transaction(function () use ($data) {
            $pengeluaran = Pengeluaran::create(array_merge($data, [
                'input_by' => auth()->id(),
                'status_validasi' => 'pending',
            ]));
            
            // Trigger event for data input
            event(new DataInputDisimpan($pengeluaran, auth()->user()));
            
            // Trigger new role-based notification system
            event(new ExpenseCreated([
                'amount' => $pengeluaran->nominal,
                'description' => $pengeluaran->keterangan ?? $pengeluaran->nama_pengeluaran ?? 'Pengeluaran baru',
                'user_name' => auth()->user()->name,
                'user_role' => auth()->user()->role?->name ?? 'Unknown',
                'pengeluaran_id' => $pengeluaran->id,
                'category' => $pengeluaran->kategori ?? 'Umum',
            ]));
            
            return $pengeluaran;
        });
    }

    public function update(Pengeluaran $pengeluaran, array $data): Pengeluaran
    {
        return DB::transaction(function () use ($pengeluaran, $data) {
            // Reset validation status if data is modified
            if ($pengeluaran->status_validasi !== 'pending') {
                $data['status_validasi'] = 'pending';
                $data['validasi_by'] = null;
                $data['validasi_at'] = null;
                $data['catatan_validasi'] = null;
            }

            $pengeluaran->update($data);
            return $pengeluaran;
        });
    }

    public function delete(Pengeluaran $pengeluaran): bool
    {
        return $pengeluaran->delete();
    }

    public function getSummary(array $filters = []): array
    {
        $query = Pengeluaran::approved()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['kategori'] ?? null, function ($query, $kategori) {
                $query->byKategori($kategori);
            });

        return [
            'total' => $query->sum('nominal'),
            'count' => $query->count(),
            'average' => $query->avg('nominal'),
            'by_category' => $query->selectRaw('kategori, SUM(nominal) as total, COUNT(*) as count')
                ->groupBy('kategori')
                ->get()
                ->keyBy('kategori'),
        ];
    }

    public function getByKategori(string $kategori, array $filters = []): array
    {
        $query = Pengeluaran::approved()
            ->byKategori($kategori)
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            });

        return [
            'data' => $query->get(),
            'summary' => [
                'total' => $query->sum('nominal'),
                'count' => $query->count(),
                'average' => $query->avg('nominal'),
            ],
        ];
    }

    public function getMonthlyTrend(int $year): array
    {
        return Pengeluaran::approved()
            ->whereYear('tanggal', $year)
            ->selectRaw('MONTH(tanggal) as month, SUM(nominal) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();
    }

    public function getDailyReport(string $date): array
    {
        $query = Pengeluaran::whereDate('tanggal', $date)
            ->with(['inputBy']);

        return [
            'pending' => $query->clone()->pending()->get(),
            'approved' => $query->clone()->approved()->get(),
            'rejected' => $query->clone()->where('status_validasi', 'ditolak')->get(),
            'summary' => [
                'total_approved' => $query->clone()->approved()->sum('nominal'),
                'total_pending' => $query->clone()->pending()->sum('nominal'),
                'count' => $query->count(),
            ],
        ];
    }

    public function getBudgetAnalysis(array $filters = []): array
    {
        $query = Pengeluaran::approved()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            });

        $totalPengeluaran = $query->sum('nominal');
        $byCategory = $query->selectRaw('kategori, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('kategori')
            ->get()
            ->map(function ($item) use ($totalPengeluaran) {
                $item->percentage = $totalPengeluaran > 0 ? round(($item->total / $totalPengeluaran) * 100, 2) : 0;
                return $item;
            })
            ->keyBy('kategori');

        return [
            'total_pengeluaran' => $totalPengeluaran,
            'by_category' => $byCategory,
            'largest_expense' => $query->orderBy('nominal', 'desc')->first(),
            'most_frequent_category' => $byCategory->sortByDesc('count')->first(),
        ];
    }
}