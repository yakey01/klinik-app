<?php

namespace App\Services\Transaksi;

use App\Events\DataInputDisimpan;
use App\Models\Pendapatan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PendapatanService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Pendapatan::with(['tindakan', 'inputBy', 'validasiBy'])
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

    public function create(array $data): Pendapatan
    {
        return DB::transaction(function () use ($data) {
            $pendapatan = Pendapatan::create(array_merge($data, [
                'input_by' => auth()->id(),
                'status_validasi' => 'pending',
            ]));
            
            // Trigger event for data input
            event(new DataInputDisimpan($pendapatan, auth()->user()));
            
            return $pendapatan;
        });
    }

    public function update(Pendapatan $pendapatan, array $data): Pendapatan
    {
        return DB::transaction(function () use ($pendapatan, $data) {
            // Reset validation status if data is modified
            if ($pendapatan->status_validasi !== 'pending') {
                $data['status_validasi'] = 'pending';
                $data['validasi_by'] = null;
                $data['validasi_at'] = null;
                $data['catatan_validasi'] = null;
            }

            $pendapatan->update($data);
            return $pendapatan;
        });
    }

    public function delete(Pendapatan $pendapatan): bool
    {
        return $pendapatan->delete();
    }

    public function getSummary(array $filters = []): array
    {
        $query = Pendapatan::approved()
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
        $query = Pendapatan::approved()
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
        return Pendapatan::approved()
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
        $query = Pendapatan::whereDate('tanggal', $date)
            ->with(['tindakan', 'inputBy']);

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
}