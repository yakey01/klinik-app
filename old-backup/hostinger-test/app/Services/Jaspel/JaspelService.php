<?php

namespace App\Services\Jaspel;

use App\Events\DataInputDisimpan;
use App\Events\JaspelSelesai;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Repositories\JaspelRepository;
use Illuminate\Support\Facades\DB;

class JaspelService
{
    protected $jaspelRepository;

    public function __construct(JaspelRepository $jaspelRepository)
    {
        $this->jaspelRepository = $jaspelRepository;
    }

    public function create(array $data): Jaspel
    {
        return DB::transaction(function () use ($data) {
            $jaspel = $this->jaspelRepository->create(array_merge($data, [
                'input_by' => auth()->id(),
                'status_validasi' => 'pending',
            ]));
            
            // Trigger event for data input
            event(new DataInputDisimpan($jaspel, auth()->user()));
            
            return $jaspel;
        });
    }

    public function update(Jaspel $jaspel, array $data): Jaspel
    {
        return DB::transaction(function () use ($jaspel, $data) {
            // Reset validation status if data is modified
            if ($jaspel->status_validasi !== 'pending') {
                $data['status_validasi'] = 'pending';
                $data['validasi_by'] = null;
                $data['validasi_at'] = null;
                $data['catatan_validasi'] = null;
            }

            return $this->jaspelRepository->update($jaspel, $data);
        });
    }

    public function delete(Jaspel $jaspel): bool
    {
        return $this->jaspelRepository->delete($jaspel);
    }

    public function generateFromTindakan(int $tindakanId): array
    {
        $tindakan = Tindakan::with(['jenisTindakan', 'shift'])->find($tindakanId);
        
        if (!$tindakan) {
            throw new \Exception('Tindakan not found');
        }

        return DB::transaction(function () use ($tindakan) {
            $jaspelRecords = [];
            
            // Generate jaspel for dokter
            if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
                $jaspelRecords[] = $this->createJaspelRecord($tindakan, 'dokter', $tindakan->dokter_id, $tindakan->jasa_dokter);
            }
            
            // Generate jaspel for paramedis
            if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
                $jaspelRecords[] = $this->createJaspelRecord($tindakan, 'paramedis', $tindakan->paramedis_id, $tindakan->jasa_paramedis);
            }
            
            // Generate jaspel for non-paramedis
            if ($tindakan->non_paramedis_id && $tindakan->jasa_non_paramedis > 0) {
                $jaspelRecords[] = $this->createJaspelRecord($tindakan, 'non_paramedis', $tindakan->non_paramedis_id, $tindakan->jasa_non_paramedis);
            }
            
            // Trigger event for jaspel completion
            event(new JaspelSelesai($tindakan, $jaspelRecords));
            
            return $jaspelRecords;
        });
    }

    protected function createJaspelRecord(Tindakan $tindakan, string $jenisJaspel, int $userId, float $nominal): Jaspel
    {
        // Check if jaspel already exists
        $existingJaspel = Jaspel::where('tindakan_id', $tindakan->id)
            ->where('user_id', $userId)
            ->where('jenis_jaspel', $jenisJaspel)
            ->first();

        if ($existingJaspel) {
            return $existingJaspel;
        }

        return Jaspel::create([
            'tindakan_id' => $tindakan->id,
            'user_id' => $userId,
            'jenis_jaspel' => $jenisJaspel,
            'nominal' => $nominal,
            'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
            'shift_id' => $tindakan->shift_id,
            'input_by' => auth()->id(),
            'status_validasi' => 'pending',
        ]);
    }

    public function getSummaryByUser(int $userId, array $filters = []): array
    {
        return $this->jaspelRepository->getSummaryByUser($userId, $filters);
    }

    public function getRekapJaspel(array $filters = []): array
    {
        $query = Jaspel::with(['user', 'tindakan.jenisTindakan'])
            ->approved()
            ->when($filters['tanggal_dari'] ?? null, function ($query, $tanggalDari) {
                $query->whereDate('tanggal', '>=', $tanggalDari);
            })
            ->when($filters['tanggal_sampai'] ?? null, function ($query, $tanggalSampai) {
                $query->whereDate('tanggal', '<=', $tanggalSampai);
            })
            ->when($filters['jenis_jaspel'] ?? null, function ($query, $jenis) {
                $query->byJenisJaspel($jenis);
            });

        $totalJaspel = $query->sum('nominal');
        $byUser = $query->selectRaw('user_id, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(function ($item) use ($totalJaspel) {
                $item->percentage = $totalJaspel > 0 ? round(($item->total / $totalJaspel) * 100, 2) : 0;
                return $item;
            });

        $byJenis = $query->selectRaw('jenis_jaspel, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('jenis_jaspel')
            ->get()
            ->keyBy('jenis_jaspel');

        return [
            'total_jaspel' => $totalJaspel,
            'by_user' => $byUser,
            'by_jenis' => $byJenis,
            'summary' => [
                'total_records' => $query->count(),
                'average_per_record' => $query->avg('nominal'),
                'highest_jaspel' => $query->orderBy('nominal', 'desc')->first(),
            ],
        ];
    }

    public function getMonthlyJaspelByUser(int $userId, int $year): array
    {
        return Jaspel::where('user_id', $userId)
            ->approved()
            ->whereYear('tanggal', $year)
            ->selectRaw('MONTH(tanggal) as month, jenis_jaspel, SUM(nominal) as total')
            ->groupBy('month', 'jenis_jaspel')
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(function ($monthData) {
                return $monthData->keyBy('jenis_jaspel')->map(function ($item) {
                    return $item->total;
                });
            })
            ->toArray();
    }

    public function processPayroll(array $userIds, string $periode): array
    {
        return DB::transaction(function () use ($userIds, $periode) {
            $results = [];
            
            foreach ($userIds as $userId) {
                $jaspelData = $this->getJaspelForPayroll($userId, $periode);
                
                if ($jaspelData['total'] > 0) {
                    $results[] = [
                        'user_id' => $userId,
                        'periode' => $periode,
                        'total_jaspel' => $jaspelData['total'],
                        'breakdown' => $jaspelData['breakdown'],
                        'records_count' => $jaspelData['count'],
                    ];
                    
                    // Mark jaspel as processed
                    Jaspel::where('user_id', $userId)
                        ->whereMonth('tanggal', date('m', strtotime($periode)))
                        ->whereYear('tanggal', date('Y', strtotime($periode)))
                        ->approved()
                        ->update(['processed_at' => now()]);
                }
            }
            
            return $results;
        });
    }

    protected function getJaspelForPayroll(int $userId, string $periode): array
    {
        $query = Jaspel::where('user_id', $userId)
            ->approved()
            ->whereMonth('tanggal', date('m', strtotime($periode)))
            ->whereYear('tanggal', date('Y', strtotime($periode)))
            ->whereNull('processed_at');

        return [
            'total' => $query->sum('nominal'),
            'count' => $query->count(),
            'breakdown' => $query->selectRaw('jenis_jaspel, SUM(nominal) as total')
                ->groupBy('jenis_jaspel')
                ->get()
                ->keyBy('jenis_jaspel')
                ->map(function ($item) {
                    return $item->total;
                }),
        ];
    }
}