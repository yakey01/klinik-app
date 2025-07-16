<?php

namespace App\Services;

use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\JumlahPasienHarian;
use App\Models\DokterUmumJaspel;
use App\Models\User;
use App\Models\Shift;
use App\Jobs\HitungJaspelPasienJob;
use App\Services\TelegramService;
use App\Services\LoggingService;
use App\Enums\TelegramNotificationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class AutomatedJaspelService
{
    protected TelegramService $telegramService;
    protected LoggingService $loggingService;

    public function __construct(TelegramService $telegramService, LoggingService $loggingService)
    {
        $this->telegramService = $telegramService;
        $this->loggingService = $loggingService;
    }

    /**
     * Process all approved transactions and generate JASPEL automatically
     */
    public function processApprovedTransactions(array $options = []): array
    {
        $startTime = microtime(true);
        $results = [
            'success' => true,
            'processed_count' => 0,
            'failed_count' => 0,
            'total_amount' => 0,
            'details' => [],
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // Process approved tindakan
            $tindakanResults = $this->processApprovedTindakan($options);
            $results['details']['tindakan'] = $tindakanResults;

            // Process approved patient counts
            $pasienResults = $this->processApprovedPasienCounts($options);
            $results['details']['pasien_counts'] = $pasienResults;

            // Calculate summary
            $results['processed_count'] = $tindakanResults['processed_count'] + $pasienResults['processed_count'];
            $results['failed_count'] = $tindakanResults['failed_count'] + $pasienResults['failed_count'];
            $results['total_amount'] = $tindakanResults['total_amount'] + $pasienResults['total_amount'];

            DB::commit();

            // Send notifications
            $this->sendProcessingNotifications($results);

            // Log successful processing
            $this->loggingService->logActivity('automated_jaspel_processing', 'success', [
                'results' => $results,
                'processing_time' => round(microtime(true) - $startTime, 2),
                'options' => $options,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();

            Log::error('AutomatedJaspelService: Processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $options,
            ]);

            $this->loggingService->logActivity('automated_jaspel_processing', 'error', [
                'error' => $e->getMessage(),
                'options' => $options,
            ]);
        }

        return $results;
    }

    /**
     * Process approved tindakan for JASPEL generation
     */
    protected function processApprovedTindakan(array $options = []): array
    {
        $results = [
            'processed_count' => 0,
            'failed_count' => 0,
            'total_amount' => 0,
            'records' => [],
        ];

        $dateFrom = $options['date_from'] ?? Carbon::today()->subDays(7);
        $dateTo = $options['date_to'] ?? Carbon::today();

        // Get approved tindakan that don't have JASPEL yet
        $approvedTindakan = Tindakan::with(['dokter.user', 'paramedis.user'])
            ->where('status', 'completed')
            ->whereBetween('tanggal_tindakan', [$dateFrom, $dateTo])
            ->whereDoesntHave('jaspels')
            ->get();

        foreach ($approvedTindakan as $tindakan) {
            try {
                $jaspelRecords = $this->generateJaspelFromTindakan($tindakan);
                
                if (!empty($jaspelRecords)) {
                    $results['processed_count']++;
                    $totalAmount = collect($jaspelRecords)->sum('nominal');
                    $results['total_amount'] += $totalAmount;
                    $results['records'][] = [
                        'tindakan_id' => $tindakan->id,
                        'jaspel_count' => count($jaspelRecords),
                        'total_amount' => $totalAmount,
                    ];
                }

            } catch (Exception $e) {
                $results['failed_count']++;
                Log::error('AutomatedJaspelService: Failed to process tindakan', [
                    'tindakan_id' => $tindakan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Process approved patient counts for doctor JASPEL
     */
    protected function processApprovedPasienCounts(array $options = []): array
    {
        $results = [
            'processed_count' => 0,
            'failed_count' => 0,
            'total_amount' => 0,
            'records' => [],
        ];

        $dateFrom = $options['date_from'] ?? Carbon::today()->subDays(7);
        $dateTo = $options['date_to'] ?? Carbon::today();

        // Get approved patient counts without JASPEL
        $approvedCounts = JumlahPasienHarian::with(['dokter.user'])
            ->where('status_validasi', 'disetujui')
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->whereDoesntHave('jaspels', function ($query) {
                $query->where('jenis_jaspel', 'pasien_harian');
            })
            ->get();

        foreach ($approvedCounts as $pasienCount) {
            try {
                $jaspelAmount = $this->generateJaspelFromPasienCount($pasienCount);
                
                if ($jaspelAmount > 0) {
                    $results['processed_count']++;
                    $results['total_amount'] += $jaspelAmount;
                    $results['records'][] = [
                        'pasien_count_id' => $pasienCount->id,
                        'doctor_id' => $pasienCount->dokter_id,
                        'total_patients' => $pasienCount->total_pasien,
                        'jaspel_amount' => $jaspelAmount,
                    ];
                }

            } catch (Exception $e) {
                $results['failed_count']++;
                Log::error('AutomatedJaspelService: Failed to process patient count', [
                    'pasien_count_id' => $pasienCount->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate JASPEL from a completed tindakan
     */
    protected function generateJaspelFromTindakan(Tindakan $tindakan): array
    {
        $jaspelRecords = [];

        // Generate JASPEL for doctor
        if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
            $jaspelRecords[] = $this->createJaspelRecord([
                'tindakan_id' => $tindakan->id,
                'user_id' => $tindakan->dokter->user_id,
                'jenis_jaspel' => 'dokter',
                'nominal' => $tindakan->jasa_dokter,
                'tanggal' => $tindakan->tanggal_tindakan,
                'shift_id' => $tindakan->shift_id,
                'keterangan' => "Jaspel dokter - {$tindakan->nama_tindakan}",
                'source_type' => 'tindakan',
                'source_id' => $tindakan->id,
            ]);
        }

        // Generate JASPEL for paramedis
        if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
            $jaspelRecords[] = $this->createJaspelRecord([
                'tindakan_id' => $tindakan->id,
                'user_id' => $tindakan->paramedis->user_id,
                'jenis_jaspel' => 'paramedis',
                'nominal' => $tindakan->jasa_paramedis,
                'tanggal' => $tindakan->tanggal_tindakan,
                'shift_id' => $tindakan->shift_id,
                'keterangan' => "Jaspel paramedis - {$tindakan->nama_tindakan}",
                'source_type' => 'tindakan',
                'source_id' => $tindakan->id,
            ]);
        }

        // Generate JASPEL for non-paramedis if applicable
        if (isset($tindakan->non_paramedis_id) && $tindakan->non_paramedis_id && $tindakan->jasa_non_paramedis > 0) {
            $jaspelRecords[] = $this->createJaspelRecord([
                'tindakan_id' => $tindakan->id,
                'user_id' => $tindakan->non_paramedis_id,
                'jenis_jaspel' => 'non_paramedis',
                'nominal' => $tindakan->jasa_non_paramedis,
                'tanggal' => $tindakan->tanggal_tindakan,
                'shift_id' => $tindakan->shift_id,
                'keterangan' => "Jaspel non-paramedis - {$tindakan->nama_tindakan}",
                'source_type' => 'tindakan',
                'source_id' => $tindakan->id,
            ]);
        }

        return array_filter($jaspelRecords);
    }

    /**
     * Generate JASPEL from approved patient count
     */
    protected function generateJaspelFromPasienCount(JumlahPasienHarian $pasienCount): float
    {
        // Get the appropriate JASPEL formula
        $formula = $this->getJaspelFormula($pasienCount->poli, $pasienCount->shift_id);
        
        if (!$formula) {
            Log::warning('No JASPEL formula found', [
                'poli' => $pasienCount->poli,
                'shift_id' => $pasienCount->shift_id,
            ]);
            return 0;
        }

        // Calculate JASPEL amount
        $jaspelAmount = $this->calculateJaspelAmount($pasienCount->total_pasien, $formula);

        if ($jaspelAmount > 0) {
            $this->createJaspelRecord([
                'user_id' => $pasienCount->dokter->user_id,
                'jenis_jaspel' => 'pasien_harian',
                'nominal' => $jaspelAmount,
                'tanggal' => $pasienCount->tanggal,
                'shift_id' => $pasienCount->shift_id,
                'keterangan' => "Jaspel {$pasienCount->poli} - {$pasienCount->total_pasien} pasien",
                'source_type' => 'pasien_count',
                'source_id' => $pasienCount->id,
            ]);
        }

        return $jaspelAmount;
    }

    /**
     * Create a JASPEL record with proper validation
     */
    protected function createJaspelRecord(array $data): ?Jaspel
    {
        // Check for duplicate JASPEL
        $existing = Jaspel::where('user_id', $data['user_id'])
            ->where('tanggal', $data['tanggal'])
            ->where('jenis_jaspel', $data['jenis_jaspel'])
            ->when(isset($data['tindakan_id']), function ($query) use ($data) {
                $query->where('tindakan_id', $data['tindakan_id']);
            })
            ->when(isset($data['source_id']), function ($query) use ($data) {
                $query->where('source_id', $data['source_id']);
            })
            ->first();

        if ($existing) {
            Log::info('JASPEL already exists, skipping creation', [
                'existing_id' => $existing->id,
                'user_id' => $data['user_id'],
                'tanggal' => $data['tanggal'],
            ]);
            return $existing;
        }

        // Create new JASPEL record
        $jaspelData = array_merge($data, [
            'status_validasi' => 'disetujui', // Auto-approve system-generated JASPEL
            'input_by' => 1, // System user
            'validasi_by' => 1, // System user
            'validasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Jaspel::create($jaspelData);
    }

    /**
     * Get JASPEL calculation formula
     */
    protected function getJaspelFormula(string $poli, ?int $shiftId): ?DokterUmumJaspel
    {
        $jenisLayanan = match (strtolower($poli)) {
            'umum' => 'umum',
            'gigi' => 'gigi',
            default => 'umum'
        };

        return DokterUmumJaspel::where('jenis_layanan', $jenisLayanan)
            ->when($shiftId, function ($query) use ($shiftId) {
                $query->where('shift_id', $shiftId);
            })
            ->where('is_active', true)
            ->orderBy('threshold_pasien', 'desc')
            ->first();
    }

    /**
     * Calculate JASPEL amount based on formula
     */
    protected function calculateJaspelAmount(int $totalPasien, DokterUmumJaspel $formula): float
    {
        if ($totalPasien < $formula->threshold_pasien) {
            return 0;
        }

        return match ($formula->tipe_perhitungan) {
            'fixed' => $formula->nominal_jaspel,
            'per_pasien' => $totalPasien * $formula->nominal_jaspel,
            'progressive' => $this->calculateProgressiveJaspel($totalPasien, $formula),
            default => $formula->nominal_jaspel,
        };
    }

    /**
     * Calculate progressive JASPEL
     */
    protected function calculateProgressiveJaspel(int $totalPasien, DokterUmumJaspel $formula): float
    {
        $baseAmount = $formula->nominal_jaspel;
        $excessPatients = max(0, $totalPasien - $formula->threshold_pasien);
        $multiplier = $formula->multiplier ?? 0;

        return $baseAmount + ($excessPatients * $multiplier);
    }

    /**
     * Get JASPEL distribution summary
     */
    public function getDistributionSummary(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::today()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::today()->endOfMonth();

        $query = Jaspel::with(['user'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('status_validasi', 'disetujui');

        $summary = [
            'total_amount' => $query->sum('nominal'),
            'total_records' => $query->count(),
            'by_type' => $query->selectRaw('jenis_jaspel, SUM(nominal) as total, COUNT(*) as count')
                ->groupBy('jenis_jaspel')
                ->get()
                ->keyBy('jenis_jaspel'),
            'by_user' => $query->selectRaw('user_id, SUM(nominal) as total, COUNT(*) as count')
                ->groupBy('user_id')
                ->with('user')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'daily_distribution' => $query->selectRaw('DATE(tanggal) as date, SUM(nominal) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return $summary;
    }

    /**
     * Schedule automatic JASPEL processing
     */
    public function scheduleAutomaticProcessing(): void
    {
        // Dispatch job for yesterday's data
        $yesterday = Carbon::yesterday();
        
        HitungJaspelPasienJob::dispatch($yesterday->toDateString())
            ->delay(now()->addMinutes(5)); // Delay to ensure all data is ready

        Log::info('Automatic JASPEL processing scheduled', [
            'date' => $yesterday->toDateString(),
            'scheduled_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Send processing notifications
     */
    protected function sendProcessingNotifications(array $results): void
    {
        if ($results['processed_count'] > 0) {
            $message = "🤖 *JASPEL Otomatis Berhasil*\n\n";
            $message .= "✅ Diproses: {$results['processed_count']} transaksi\n";
            $message .= "💰 Total JASPEL: Rp " . number_format($results['total_amount'], 0, ',', '.') . "\n";
            
            if ($results['failed_count'] > 0) {
                $message .= "❌ Gagal: {$results['failed_count']} transaksi\n";
            }

            $this->telegramService->sendMessage(
                $message,
                TelegramNotificationType::JASPEL_AUTOMATIC_PROCESSING
            );
        }

        if ($results['failed_count'] > 0 && $results['processed_count'] === 0) {
            $message = "⚠️ *JASPEL Otomatis Gagal*\n\n";
            $message .= "❌ Semua transaksi gagal diproses\n";
            $message .= "🔍 Silakan periksa log untuk detail error";

            $this->telegramService->sendMessage(
                $message,
                TelegramNotificationType::JASPEL_PROCESSING_ERROR
            );
        }
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);
        
        return [
            'total_processed' => Jaspel::whereDate('created_at', '>=', $startDate)
                ->where('input_by', 1) // System generated
                ->count(),
            'total_amount' => Jaspel::whereDate('created_at', '>=', $startDate)
                ->where('input_by', 1)
                ->sum('nominal'),
            'by_type' => Jaspel::whereDate('created_at', '>=', $startDate)
                ->where('input_by', 1)
                ->selectRaw('jenis_jaspel, COUNT(*) as count, SUM(nominal) as total')
                ->groupBy('jenis_jaspel')
                ->get(),
            'daily_stats' => Jaspel::whereDate('created_at', '>=', $startDate)
                ->where('input_by', 1)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(nominal) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    /**
     * Validate JASPEL calculations
     */
    public function validateCalculations(array $jaspelIds): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'summary' => [
                'total_checked' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
            ],
        ];

        foreach ($jaspelIds as $jaspelId) {
            $jaspel = Jaspel::with(['tindakan', 'user'])->find($jaspelId);
            
            if (!$jaspel) {
                continue;
            }

            $isValid = $this->validateSingleJaspel($jaspel);
            $results['summary']['total_checked']++;

            if ($isValid) {
                $results['valid'][] = $jaspelId;
                $results['summary']['valid_count']++;
            } else {
                $results['invalid'][] = $jaspelId;
                $results['summary']['invalid_count']++;
            }
        }

        return $results;
    }

    /**
     * Validate a single JASPEL calculation
     */
    protected function validateSingleJaspel(Jaspel $jaspel): bool
    {
        // Add validation logic based on your business rules
        if ($jaspel->nominal <= 0) {
            return false;
        }

        if ($jaspel->jenis_jaspel === 'pasien_harian') {
            // Validate patient count based JASPEL
            return $this->validatePasienHarianJaspel($jaspel);
        }

        if ($jaspel->tindakan_id) {
            // Validate tindakan based JASPEL
            return $this->validateTindakanJaspel($jaspel);
        }

        return true;
    }

    /**
     * Validate patient count based JASPEL
     */
    protected function validatePasienHarianJaspel(Jaspel $jaspel): bool
    {
        // Implementation depends on your validation rules
        return $jaspel->nominal > 0 && $jaspel->user_id > 0;
    }

    /**
     * Validate tindakan based JASPEL
     */
    protected function validateTindakanJaspel(Jaspel $jaspel): bool
    {
        if (!$jaspel->tindakan) {
            return false;
        }

        // Validate against tindakan amounts
        $expectedAmount = match ($jaspel->jenis_jaspel) {
            'dokter' => $jaspel->tindakan->jasa_dokter,
            'paramedis' => $jaspel->tindakan->jasa_paramedis,
            'non_paramedis' => $jaspel->tindakan->jasa_non_paramedis ?? 0,
            default => 0,
        };

        return abs($jaspel->nominal - $expectedAmount) < 0.01; // Allow for floating point precision
    }
}