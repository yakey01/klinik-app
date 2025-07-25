<?php

namespace App\Services;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\JenisTindakan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JaspelCalculationService
{
    /**
     * Calculate Jaspel for validated tindakan
     */
    public function calculateJaspelFromTindakan(Tindakan $tindakan)
    {
        // Only calculate if tindakan is validated/approved
        if ($tindakan->status_validasi !== 'approved') {
            return null;
        }

        // Get the jenis tindakan for fee calculation
        $jenisTindakan = $tindakan->jenisTindakan;
        if (!$jenisTindakan) {
            return null;
        }

        // Get dokter and paramedis involved
        $dokter = $tindakan->dokter;
        $paramedis = $tindakan->paramedis; // Fixed: use paramedis relation instead of perawat

        $jaspelRecords = [];

        // Calculate Jaspel for dokter
        if ($dokter) {
            $jaspelDokter = $this->createJaspelForDokter($tindakan, $dokter, $jenisTindakan);
            if ($jaspelDokter) {
                $jaspelRecords[] = $jaspelDokter;
            }
        }

        // Calculate Jaspel for paramedis
        if ($paramedis) {
            $jaspelParamedis = $this->createJaspelForParamedis($tindakan, $paramedis, $jenisTindakan);
            if ($jaspelParamedis) {
                $jaspelRecords[] = $jaspelParamedis;
            }
        }

        return $jaspelRecords;
    }

    /**
     * Create Jaspel record for dokter
     */
    private function createJaspelForDokter(Tindakan $tindakan, $dokter, JenisTindakan $jenisTindakan)
    {
        // Dokter could be Dokter model, need to get User
        if ($dokter instanceof \App\Models\Dokter) {
            $user = User::find($dokter->user_id);
            if (!$user) {
                return null; // No user found for this dokter
            }
        } else {
            $user = $dokter;
        }

        // Determine jaspel type based on dokter role
        $jaspelType = $user->hasRole('dokter_spesialis') ? 'dokter_spesialis' : 'dokter_umum';
        
        // Calculate fee based on tarif
        $nominalJaspel = $this->calculateDokterFee($jenisTindakan->tarif, $jaspelType);

        // Check if Jaspel already exists for this tindakan and dokter
        $existingJaspel = Jaspel::where('user_id', $user->id)
            ->where('tindakan_id', $tindakan->id)
            ->first();

        if ($existingJaspel) {
            return $existingJaspel;
        }

        // Create new Jaspel record
        return Jaspel::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => $user->id,
            'jenis_jaspel' => $jaspelType,
            'nominal' => $nominalJaspel,
            'total_jaspel' => $nominalJaspel,
            'tanggal' => $tindakan->tanggal_tindakan,
            'shift_id' => null, // Set as nullable to avoid foreign key issues
            'input_by' => $tindakan->validated_by ?? 1,
            'status_validasi' => 'pending',
        ]);
    }

    /**
     * Create Jaspel record for paramedis
     */
    private function createJaspelForParamedis(Tindakan $tindakan, $paramedis, JenisTindakan $jenisTindakan)
    {
        // Paramedis could be Pegawai model, need to get User
        if ($paramedis instanceof \App\Models\Pegawai) {
            $user = User::find($paramedis->user_id);
            if (!$user) {
                return null; // No user found for this pegawai
            }
        } else {
            $user = $paramedis;
        }

        // Calculate fee for paramedis with proper jenis_tindakan context
        $nominalJaspel = $this->calculateParamedisFee($jenisTindakan->tarif, $jenisTindakan);

        // Check if Jaspel already exists for this tindakan and paramedis
        $existingJaspel = Jaspel::where('user_id', $user->id)
            ->where('tindakan_id', $tindakan->id)
            ->first();

        if ($existingJaspel) {
            return $existingJaspel;
        }

        // Create new Jaspel record
        return Jaspel::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => $user->id,
            'jenis_jaspel' => 'paramedis',
            'nominal' => $nominalJaspel,
            'total_jaspel' => $nominalJaspel,
            'tanggal' => $tindakan->tanggal_tindakan,
            'shift_id' => null, // Set as nullable to avoid foreign key issues
            'input_by' => $tindakan->validated_by ?? 1,
            'status_validasi' => 'pending',
        ]);
    }

    /**
     * Calculate dokter fee based on tarif and type
     */
    private function calculateDokterFee($tarif, $type)
    {
        // Fee calculation logic based on clinic policy
        // This can be customized based on actual business rules
        if ($type === 'dokter_spesialis') {
            return $tarif * 0.50; // 50% for specialist
        } else {
            return $tarif * 0.40; // 40% for general practitioner
        }
    }

    /**
     * Calculate paramedis fee based on tarif and jenis_tindakan
     */
    private function calculateParamedisFee($tarif, $jenisTindakan = null)
    {
        // Prioritize jenis_tindakan persentase_jaspel if available
        if ($jenisTindakan && $jenisTindakan->persentase_jaspel > 0) {
            return $tarif * ($jenisTindakan->persentase_jaspel / 100);
        }
        
        // Fallback to standard 15% for paramedis
        return $tarif * 0.15;
    }

    /**
     * Get Jaspel summary for a user
     */
    public function getJaspelSummary(User $user, $month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $query = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        return [
            'total' => $query->sum('nominal'),
            'pending' => $query->where('status_validasi', 'pending')->sum('nominal'),
            'approved' => $query->where('status_validasi', 'disetujui')->sum('nominal'),
            'rejected' => $query->where('status_validasi', 'ditolak')->sum('nominal'),
            'count' => [
                'total' => $query->count(),
                'pending' => $query->where('status_validasi', 'pending')->count(),
                'approved' => $query->where('status_validasi', 'disetujui')->count(),
                'rejected' => $query->where('status_validasi', 'ditolak')->count(),
            ]
        ];
    }

    /**
     * Get detailed Jaspel history for a user
     */
    public function getJaspelHistory(User $user, $filters = [])
    {
        $query = Jaspel::where('user_id', $user->id)
            ->with(['validasiBy', 'user', 'tindakan']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status_validasi', $filters['status']);
        }

        if (isset($filters['month'])) {
            $query->whereMonth('tanggal', $filters['month']);
        }

        if (isset($filters['year'])) {
            $query->whereYear('tanggal', $filters['year']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('tanggal', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('tanggal', '<=', $filters['date_to']);
        }

        return $query->orderBy('tanggal', 'desc');
    }

    /**
     * Get Jaspel statistics for dashboard
     */
    public function getJaspelStatistics(User $user)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get current month summary
        $currentMonthSummary = $this->getJaspelSummary($user, $currentMonth, $currentYear);

        // Get last 6 months trend
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $summary = $this->getJaspelSummary($user, $date->month, $date->year);
            $trend[] = [
                'month' => $date->format('M Y'),
                'total' => $summary['approved'],
            ];
        }

        // Get recent Jaspel
        $recentJaspel = Jaspel::where('user_id', $user->id)
            ->with(['validasiBy'])
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        return [
            'current_month' => $currentMonthSummary,
            'trend' => $trend,
            'recent' => $recentJaspel,
        ];
    }

    /**
     * Bulk calculate Jaspel for validated tindakan
     */
    public function bulkCalculateFromValidatedTindakan($startDate = null, $endDate = null)
    {
        $query = Tindakan::where('status_validasi', 'approved')
            ->with(['dokter', 'paramedis', 'jenisTindakan', 'pasien']);

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        $tindakanList = $query->get();
        $createdJaspel = [];

        DB::beginTransaction();
        try {
            foreach ($tindakanList as $tindakan) {
                $jaspelRecords = $this->calculateJaspelFromTindakan($tindakan);
                if ($jaspelRecords) {
                    $createdJaspel = array_merge($createdJaspel, $jaspelRecords);
                }
            }

            DB::commit();
            return $createdJaspel;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}