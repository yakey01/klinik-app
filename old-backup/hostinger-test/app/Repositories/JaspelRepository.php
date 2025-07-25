<?php

namespace App\Repositories;

use App\Models\Jaspel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class JaspelRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Jaspel::with(['user', 'tindakan', 'shift', 'inputBy', 'validasiBy'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($filters['jenis_jaspel'] ?? null, function ($query, $jenis) {
                $query->byJenisJaspel($jenis);
            })
            ->when($filters['status_validasi'] ?? null, function ($query, $status) {
                $query->byStatus($status);
            });

        return $query->latest('tanggal')->paginate($perPage);
    }

    public function create(array $data): Jaspel
    {
        return Jaspel::create($data);
    }

    public function update(Jaspel $jaspel, array $data): Jaspel
    {
        $jaspel->update($data);
        return $jaspel;
    }

    public function delete(Jaspel $jaspel): bool
    {
        return $jaspel->delete();
    }

    public function getByUser(int $userId, array $filters = []): Collection
    {
        return Jaspel::where('user_id', $userId)
            ->with(['tindakan', 'shift', 'inputBy', 'validasiBy'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['jenis_jaspel'] ?? null, function ($query, $jenis) {
                $query->byJenisJaspel($jenis);
            })
            ->when($filters['status_validasi'] ?? null, function ($query, $status) {
                $query->byStatus($status);
            })
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getSummaryByUser(int $userId, array $filters = []): array
    {
        $query = Jaspel::where('user_id', $userId)
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            });

        $totalJaspel = $query->approved()->sum('nominal');
        $totalPending = $query->pending()->sum('nominal');
        $totalRecords = $query->count();

        $byJenisJaspel = $query->approved()
            ->selectRaw('jenis_jaspel, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('jenis_jaspel')
            ->get()
            ->keyBy('jenis_jaspel');

        $byMonth = $query->approved()
            ->selectRaw('MONTH(tanggal) as month, SUM(nominal) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return [
            'total_jaspel' => $totalJaspel,
            'total_pending' => $totalPending,
            'total_records' => $totalRecords,
            'average_per_record' => $totalRecords > 0 ? $totalJaspel / $totalRecords : 0,
            'by_jenis_jaspel' => $byJenisJaspel,
            'by_month' => $byMonth,
        ];
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Jaspel::byDateRange($startDate, $endDate)
            ->with(['user', 'tindakan', 'shift'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getPendingJaspel(): Collection
    {
        return Jaspel::pending()
            ->with(['user', 'tindakan', 'shift', 'inputBy'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getApprovedJaspel(array $filters = []): Collection
    {
        return Jaspel::approved()
            ->with(['user', 'tindakan', 'shift'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getJaspelForPayroll(int $userId, string $periode): array
    {
        $query = Jaspel::where('user_id', $userId)
            ->approved()
            ->whereMonth('tanggal', date('m', strtotime($periode)))
            ->whereYear('tanggal', date('Y', strtotime($periode)))
            ->whereNull('processed_at');

        return [
            'total' => $query->sum('nominal'),
            'count' => $query->count(),
            'records' => $query->get(),
            'breakdown' => $query->selectRaw('jenis_jaspel, SUM(nominal) as total')
                ->groupBy('jenis_jaspel')
                ->get()
                ->keyBy('jenis_jaspel')
                ->map(function ($item) {
                    return $item->total;
                }),
        ];
    }

    public function getTopEarners(array $filters = []): Collection
    {
        return Jaspel::approved()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->selectRaw('user_id, SUM(nominal) as total_jaspel, COUNT(*) as total_records')
            ->groupBy('user_id')
            ->with('user')
            ->orderBy('total_jaspel', 'desc')
            ->limit(10)
            ->get();
    }

    public function getJaspelStatistics(array $filters = []): array
    {
        $query = Jaspel::query()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            });

        $totalJaspel = $query->approved()->sum('nominal');
        $totalPending = $query->pending()->sum('nominal');
        $totalRecords = $query->count();

        $byStatus = $query->selectRaw('status_validasi, COUNT(*) as count, SUM(nominal) as total')
            ->groupBy('status_validasi')
            ->get()
            ->keyBy('status_validasi');

        $byJenisJaspel = $query->approved()
            ->selectRaw('jenis_jaspel, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('jenis_jaspel')
            ->get()
            ->keyBy('jenis_jaspel');

        return [
            'total_jaspel' => $totalJaspel,
            'total_pending' => $totalPending,
            'total_records' => $totalRecords,
            'average_per_record' => $totalRecords > 0 ? $totalJaspel / $totalRecords : 0,
            'by_status' => $byStatus,
            'by_jenis_jaspel' => $byJenisJaspel,
        ];
    }

    public function getMonthlyJaspelTrend(int $year): array
    {
        return Jaspel::approved()
            ->whereYear('tanggal', $year)
            ->selectRaw('MONTH(tanggal) as month, jenis_jaspel, SUM(nominal) as total')
            ->groupBy('month', 'jenis_jaspel')
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(function ($monthData) {
                return [
                    'total' => $monthData->sum('total'),
                    'by_jenis' => $monthData->keyBy('jenis_jaspel')->map(function ($item) {
                        return $item->total;
                    }),
                ];
            })
            ->toArray();
    }

    public function getUnprocessedJaspelForPayroll(): Collection
    {
        return Jaspel::approved()
            ->whereNull('processed_at')
            ->with(['user', 'tindakan'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }
}