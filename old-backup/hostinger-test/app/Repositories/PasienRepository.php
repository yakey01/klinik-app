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
        $maxDate = now()->subYears($minAge)->endOfYear();
        $minDate = now()->subYears($maxAge + 1)->startOfYear();
        
        return Pasien::whereBetween('tanggal_lahir', [$minDate, $maxDate])
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

        // Calculate age groups using Laravel's date handling
        $allPatients = Pasien::select('tanggal_lahir')->get();
        $byAgeGroup = collect();
        
        foreach ($allPatients as $patient) {
            $age = now()->diffInYears($patient->tanggal_lahir);
            $ageGroup = match(true) {
                $age < 18 => '0-17',
                $age >= 18 && $age <= 30 => '18-30',
                $age >= 31 && $age <= 50 => '31-50',
                $age >= 51 && $age <= 65 => '51-65',
                default => '65+'
            };
            
            $byAgeGroup->push((object)['age_group' => $ageGroup]);
        }
        
        $byAgeGroup = $byAgeGroup->groupBy('age_group')->map(function ($group) {
            return (object)['age_group' => $group->first()->age_group, 'count' => $group->count()];
        })->keyBy('age_group');

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