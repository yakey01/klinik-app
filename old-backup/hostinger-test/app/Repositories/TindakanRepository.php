<?php

namespace App\Repositories;

use App\Models\Tindakan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TindakanRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Tindakan::with(['pasien', 'jenisTindakan', 'dokter', 'shift'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal_tindakan', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal_tindakan', '<=', $tanggalSampai);
            })
            ->when($filters['dokter_id'] ?? null, function ($query, $dokterId) {
                $query->byDokter($dokterId);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->byStatus($status);
            });

        return $query->latest('tanggal_tindakan')->paginate($perPage);
    }

    public function create(array $data): Tindakan
    {
        return Tindakan::create($data);
    }

    public function update(Tindakan $tindakan, array $data): Tindakan
    {
        $tindakan->update($data);
        return $tindakan;
    }

    public function delete(Tindakan $tindakan): bool
    {
        return $tindakan->delete();
    }

    public function getByDokter(int $dokterId, array $filters = []): Collection
    {
        return Tindakan::byDokter($dokterId)
            ->with(['pasien', 'jenisTindakan', 'shift'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal_tindakan', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal_tindakan', '<=', $tanggalSampai);
            })
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();
    }

    public function getByPasien(int $pasienId, array $filters = []): Collection
    {
        return Tindakan::where('pasien_id', $pasienId)
            ->with(['jenisTindakan', 'dokter', 'shift'])
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal_tindakan', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal_tindakan', '<=', $tanggalSampai);
            })
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return Tindakan::byDateRange($startDate, $endDate)
            ->with(['pasien', 'jenisTindakan', 'dokter', 'shift'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Tindakan::query()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal_tindakan', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal_tindakan', '<=', $tanggalSampai);
            })
            ->when($filters['dokter_id'] ?? null, function ($query, $dokterId) {
                $query->byDokter($dokterId);
            });

        $totalTindakan = $query->count();
        $totalTarif = $query->sum('tarif');
        $byStatus = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $byDokter = $query->selectRaw('dokter_id, COUNT(*) as count, SUM(tarif) as total_tarif')
            ->groupBy('dokter_id')
            ->with('dokter')
            ->get();

        $byJenisTindakan = $query->selectRaw('jenis_tindakan_id, COUNT(*) as count, SUM(tarif) as total_tarif')
            ->groupBy('jenis_tindakan_id')
            ->with('jenisTindakan')
            ->get();

        return [
            'total_tindakan' => $totalTindakan,
            'total_tarif' => $totalTarif,
            'average_tarif' => $totalTindakan > 0 ? $totalTarif / $totalTindakan : 0,
            'by_status' => $byStatus,
            'by_dokter' => $byDokter,
            'by_jenis_tindakan' => $byJenisTindakan,
        ];
    }

    public function getRevenueByPeriod(string $startDate, string $endDate): array
    {
        $query = Tindakan::byDateRange($startDate, $endDate)
            ->byStatus('selesai');

        $totalRevenue = $query->sum('tarif');
        $byDate = $query->selectRaw('DATE(tanggal_tindakan) as date, SUM(tarif) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $byDokter = $query->selectRaw('dokter_id, SUM(tarif) as total')
            ->groupBy('dokter_id')
            ->with('dokter')
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'by_date' => $byDate,
            'by_dokter' => $byDokter,
        ];
    }

    public function getDailyTindakan(string $date): Collection
    {
        return Tindakan::whereDate('tanggal_tindakan', $date)
            ->with(['pasien', 'jenisTindakan', 'dokter', 'shift'])
            ->orderBy('tanggal_tindakan')
            ->get();
    }

    public function getMonthlyTrend(int $year): array
    {
        return Tindakan::whereYear('tanggal_tindakan', $year)
            ->selectRaw('MONTH(tanggal_tindakan) as month, COUNT(*) as count, SUM(tarif) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();
    }

    public function getTopPerformingDokter(array $filters = []): Collection
    {
        return Tindakan::query()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal_tindakan', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal_tindakan', '<=', $tanggalSampai);
            })
            ->selectRaw('dokter_id, COUNT(*) as total_tindakan, SUM(tarif) as total_revenue')
            ->groupBy('dokter_id')
            ->with('dokter')
            ->orderBy('total_tindakan', 'desc')
            ->limit(10)
            ->get();
    }

    public function getPendingTindakan(): Collection
    {
        return Tindakan::byStatus('pending')
            ->with(['pasien', 'jenisTindakan', 'dokter', 'shift'])
            ->orderBy('tanggal_tindakan')
            ->get();
    }

    public function getCompletedTindakanWithoutJaspel(): Collection
    {
        return Tindakan::byStatus('selesai')
            ->whereDoesntHave('jaspel')
            ->with(['pasien', 'jenisTindakan', 'dokter', 'shift'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();
    }
}