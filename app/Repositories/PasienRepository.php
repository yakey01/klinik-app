<?php

namespace App\Repositories;

use App\Models\Pasien;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PasienRepository
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = Pasien::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$search}%")
                  ->orWhere('no_telepon', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Pasien
    {
        return Pasien::create($data);
    }

    public function update(Pasien $pasien, array $data): Pasien
    {
        $pasien->update($data);
        return $pasien;
    }

    public function delete(Pasien $pasien): bool
    {
        return $pasien->delete();
    }

    public function findByNoRekamMedis(string $noRekamMedis): ?Pasien
    {
        return Pasien::where('no_rekam_medis', $noRekamMedis)->first();
    }

    public function findByEmail(string $email): ?Pasien
    {
        return Pasien::where('email', $email)->first();
    }

    public function findByNoTelepon(string $noTelepon): ?Pasien
    {
        return Pasien::where('no_telepon', $noTelepon)->first();
    }

    public function getActivePatients(): Collection
    {
        return Pasien::whereHas('tindakan', function ($query) {
            $query->where('tanggal_tindakan', '>=', now()->subMonths(6));
        })->get();
    }

    public function getPatientsByAge(int $minAge, int $maxAge): Collection
    {
        return Pasien::whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN ? AND ?', [$minAge, $maxAge])
            ->get();
    }

    public function getPatientsByGender(string $gender): Collection
    {
        return Pasien::byGender($gender)->get();
    }

    public function getPatientStatistics(): array
    {
        $total = Pasien::count();
        $byGender = Pasien::selectRaw('jenis_kelamin, COUNT(*) as count')
            ->groupBy('jenis_kelamin')
            ->get()
            ->keyBy('jenis_kelamin');

        $byAgeGroup = Pasien::selectRaw('
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) < 18 THEN "0-17"
                WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 18 AND 30 THEN "18-30"
                WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 31 AND 50 THEN "31-50"
                WHEN TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN 51 AND 65 THEN "51-65"
                ELSE "65+"
            END as age_group,
            COUNT(*) as count
        ')
            ->groupBy('age_group')
            ->get()
            ->keyBy('age_group');

        return [
            'total' => $total,
            'by_gender' => $byGender,
            'by_age_group' => $byAgeGroup,
        ];
    }

    public function searchPatients(string $query): Collection
    {
        return Pasien::where('nama', 'like', "%{$query}%")
            ->orWhere('no_rekam_medis', 'like', "%{$query}%")
            ->orWhere('no_telepon', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get();
    }

    public function getRecentPatients(int $limit = 10): Collection
    {
        return Pasien::latest()->limit($limit)->get();
    }

    public function getPatientVisitHistory(int $pasienId): Collection
    {
        return Pasien::find($pasienId)
            ->tindakan()
            ->with(['jenisTindakan', 'dokter', 'shift'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();
    }
}