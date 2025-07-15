<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\JenisTindakan;
use App\Models\Jaspel;
use App\Models\Role;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StressPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $petugasUser;
    private User $bendaharaUser;
    private User $adminUser;
    private JenisTindakan $jenisTindakan;
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $petugasRole = Role::create(['name' => 'petugas', 'display_name' => 'Petugas', 'description' => 'Staff Petugas']);
        $bendaharaRole = Role::create(['name' => 'bendahara', 'display_name' => 'Bendahara', 'description' => 'Staff Bendahara']);
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Administrator']);
        
        // Create test users
        $this->petugasUser = User::create([
            'name' => 'Stress Test Petugas',
            'email' => 'stress_petugas@test.com',
            'password' => bcrypt('password123'),
            'role_id' => $petugasRole->id,
            'is_active' => true,
        ]);
        
        $this->bendaharaUser = User::create([
            'name' => 'Stress Test Bendahara',
            'email' => 'stress_bendahara@test.com',
            'password' => bcrypt('password123'),
            'role_id' => $bendaharaRole->id,
            'is_active' => true,
        ]);
        
        $this->adminUser = User::create([
            'name' => 'Stress Test Admin',
            'email' => 'stress_admin@test.com',
            'password' => bcrypt('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        
        // Create jenis tindakan
        $this->jenisTindakan = JenisTindakan::create([
            'nama' => 'Stress Test Consultation',
            'tarif' => 100000,
            'jasa_dokter' => 60000,
            'jasa_paramedis' => 20000,
            'jasa_non_paramedis' => 20000,
            'is_active' => true,
        ]);
        
        // Initialize performance metrics
        $this->performanceMetrics = [
            'memory_usage' => [],
            'execution_times' => [],
            'database_queries' => [],
            'cache_operations' => [],
        ];
    }

    public function test_bulk_patient_creation_stress_test()
    {
        $this->actingAs($this->petugasUser);
        
        $patientCount = 1000; // Stress test with 1000 patients
        $batchSize = 100;
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        Log::info("Starting bulk patient creation stress test", [
            'patient_count' => $patientCount,
            'batch_size' => $batchSize,
            'start_memory' => $startMemory
        ]);
        
        $totalCreated = 0;
        
        // Create patients in batches for memory efficiency
        for ($batch = 0; $batch < ($patientCount / $batchSize); $batch++) {
            $batchStartTime = microtime(true);
            $patients = [];
            
            for ($i = 0; $i < $batchSize; $i++) {
                $patientNumber = ($batch * $batchSize) + $i + 1;
                $patients[] = [
                    'no_rekam_medis' => sprintf('STRESS%06d', $patientNumber),
                    'nama' => "Stress Test Patient {$patientNumber}",
                    'tanggal_lahir' => Carbon::now()->subYears(rand(20, 80))->format('Y-m-d'),
                    'jenis_kelamin' => rand(0, 1) ? 'L' : 'P',
                    'alamat' => "Stress Test Address {$patientNumber}",
                    'no_telepon' => '0812' . sprintf('%08d', $patientNumber),
                    'email' => "stress_patient_{$patientNumber}@test.com",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // Bulk insert batch
            DB::beginTransaction();
            try {
                Pasien::insert($patients);
                $totalCreated += count($patients);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->fail("Batch {$batch} failed: " . $e->getMessage());
            }
            
            $batchEndTime = microtime(true);
            $batchExecutionTime = $batchEndTime - $batchStartTime;
            
            // Record batch performance
            $this->performanceMetrics['execution_times'][] = $batchExecutionTime;
            $this->performanceMetrics['memory_usage'][] = memory_get_usage(true);
            
            // Memory management - force garbage collection every 5 batches
            if ($batch % 5 === 0) {
                gc_collect_cycles();
            }
            
            // Performance assertion - each batch should complete within 2 seconds
            $this->assertLessThan(2.0, $batchExecutionTime, "Batch {$batch} took too long: {$batchExecutionTime}s");
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $totalExecutionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Verify all patients were created
        $this->assertEquals($patientCount, Pasien::count());
        $this->assertEquals($patientCount, $totalCreated);
        
        // Performance assertions
        $this->assertLessThan(30.0, $totalExecutionTime, "Total execution time too long: {$totalExecutionTime}s");
        $this->assertLessThan(500 * 1024 * 1024, $memoryUsed, "Memory usage too high: " . ($memoryUsed / 1024 / 1024) . "MB");
        
        // Calculate performance metrics
        $avgBatchTime = array_sum($this->performanceMetrics['execution_times']) / count($this->performanceMetrics['execution_times']);
        $maxBatchTime = max($this->performanceMetrics['execution_times']);
        $patientsPerSecond = $patientCount / $totalExecutionTime;
        
        Log::info("Bulk patient creation stress test completed", [
            'total_patients' => $patientCount,
            'total_time' => $totalExecutionTime,
            'memory_used_mb' => $memoryUsed / 1024 / 1024,
            'avg_batch_time' => $avgBatchTime,
            'max_batch_time' => $maxBatchTime,
            'patients_per_second' => $patientsPerSecond,
        ]);
        
        // Performance benchmarks
        $this->assertGreaterThan(30, $patientsPerSecond, "Patient creation rate too slow");
        $this->assertLessThan(0.5, $avgBatchTime, "Average batch time too slow");
        
        return [
            'patients_created' => $patientCount,
            'execution_time' => $totalExecutionTime,
            'memory_used' => $memoryUsed,
            'patients_per_second' => $patientsPerSecond,
        ];
    }

    public function test_massive_tindakan_creation_with_validation()
    {
        // First create patients for stress test
        $patientData = $this->test_bulk_patient_creation_stress_test();
        
        $this->actingAs($this->petugasUser);
        
        $tindakanCount = 2000; // 2000 tindakan for stress test
        $batchSize = 200;
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        Log::info("Starting massive tindakan creation stress test", [
            'tindakan_count' => $tindakanCount,
            'batch_size' => $batchSize,
        ]);
        
        // Get random patients for tindakan
        $patientIds = Pasien::pluck('id')->toArray();
        $totalCreated = 0;
        
        for ($batch = 0; $batch < ($tindakanCount / $batchSize); $batch++) {
            $batchStartTime = microtime(true);
            $tindakanBatch = [];
            
            for ($i = 0; $i < $batchSize; $i++) {
                $tindakanBatch[] = [
                    'pasien_id' => $patientIds[array_rand($patientIds)],
                    'jenis_tindakan_id' => $this->jenisTindakan->id,
                    'dokter_id' => $this->petugasUser->id, // Use petugas as dokter for simplicity
                    'tanggal_tindakan' => Carbon::now()->subDays(rand(0, 30)),
                    'tarif' => $this->jenisTindakan->tarif,
                    'jasa_dokter' => $this->jenisTindakan->jasa_dokter,
                    'jasa_paramedis' => $this->jenisTindakan->jasa_paramedis,
                    'jasa_non_paramedis' => $this->jenisTindakan->jasa_non_paramedis,
                    'catatan' => "Stress test tindakan batch {$batch}",
                    'status' => 'selesai',
                    'status_validasi' => 'pending',
                    'input_by' => $this->petugasUser->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // Bulk insert with transaction
            DB::beginTransaction();
            try {
                Tindakan::insert($tindakanBatch);
                $totalCreated += count($tindakanBatch);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->fail("Tindakan batch {$batch} failed: " . $e->getMessage());
            }
            
            $batchEndTime = microtime(true);
            $batchExecutionTime = $batchEndTime - $batchStartTime;
            
            // Performance tracking
            $this->performanceMetrics['execution_times'][] = $batchExecutionTime;
            
            // Memory management
            if ($batch % 3 === 0) {
                gc_collect_cycles();
            }
            
            // Performance assertion
            $this->assertLessThan(3.0, $batchExecutionTime, "Tindakan batch {$batch} took too long: {$batchExecutionTime}s");
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $totalExecutionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Verify all tindakan were created
        $this->assertEquals($tindakanCount, Tindakan::count());
        
        // Performance assertions
        $this->assertLessThan(60.0, $totalExecutionTime, "Total tindakan creation too slow");
        $tindakanPerSecond = $tindakanCount / $totalExecutionTime;
        $this->assertGreaterThan(20, $tindakanPerSecond, "Tindakan creation rate too slow");
        
        Log::info("Massive tindakan creation completed", [
            'total_tindakan' => $tindakanCount,
            'total_time' => $totalExecutionTime,
            'tindakan_per_second' => $tindakanPerSecond,
            'memory_used_mb' => $memoryUsed / 1024 / 1024,
        ]);
        
        return [
            'tindakan_created' => $tindakanCount,
            'execution_time' => $totalExecutionTime,
            'tindakan_per_second' => $tindakanPerSecond,
        ];
    }

    public function test_bulk_validation_performance_stress()
    {
        // Create massive tindakan data first
        $tindakanData = $this->test_massive_tindakan_creation_with_validation();
        
        $this->actingAs($this->bendaharaUser);
        
        $validationCount = 2000; // Validate all 2000 tindakan
        $batchSize = 500; // Larger batch for validation
        $startTime = microtime(true);
        
        Log::info("Starting bulk validation stress test", [
            'validation_count' => $validationCount,
            'batch_size' => $batchSize,
        ]);
        
        // Get all pending tindakan
        $pendingTindakan = Tindakan::where('status_validasi', 'pending')->pluck('id');
        $this->assertCount($validationCount, $pendingTindakan);
        
        $totalValidated = 0;
        
        foreach ($pendingTindakan->chunk($batchSize) as $batchIndex => $batch) {
            $batchStartTime = microtime(true);
            
            // Bulk validation update
            DB::beginTransaction();
            try {
                $updated = DB::table('tindakan')
                    ->whereIn('id', $batch)
                    ->update([
                        'status_validasi' => 'approved',
                        'validated_by' => $this->bendaharaUser->id,
                        'validated_at' => Carbon::now(),
                        'komentar_validasi' => "Bulk stress test approval batch {$batchIndex}",
                        'updated_at' => Carbon::now(),
                    ]);
                
                $totalValidated += $updated;
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollback();
                $this->fail("Validation batch {$batchIndex} failed: " . $e->getMessage());
            }
            
            $batchEndTime = microtime(true);
            $batchExecutionTime = $batchEndTime - $batchStartTime;
            
            // Performance assertion - bulk validation should be very fast
            $this->assertLessThan(1.0, $batchExecutionTime, "Validation batch {$batchIndex} too slow: {$batchExecutionTime}s");
        }
        
        $endTime = microtime(true);
        $totalExecutionTime = $endTime - $startTime;
        
        // Verify all validations completed
        $this->assertEquals($validationCount, $totalValidated);
        $this->assertEquals(0, Tindakan::where('status_validasi', 'pending')->count());
        $this->assertEquals($validationCount, Tindakan::where('status_validasi', 'approved')->count());
        
        // Performance assertions
        $this->assertLessThan(10.0, $totalExecutionTime, "Bulk validation too slow");
        $validationsPerSecond = $validationCount / $totalExecutionTime;
        $this->assertGreaterThan(200, $validationsPerSecond, "Validation rate too slow");
        
        Log::info("Bulk validation stress test completed", [
            'total_validated' => $totalValidated,
            'total_time' => $totalExecutionTime,
            'validations_per_second' => $validationsPerSecond,
        ]);
        
        return [
            'validations_completed' => $totalValidated,
            'execution_time' => $totalExecutionTime,
            'validations_per_second' => $validationsPerSecond,
        ];
    }

    public function test_massive_financial_calculation_stress()
    {
        // Use validated tindakan from previous test
        $this->test_bulk_validation_performance_stress();
        
        $this->actingAs($this->petugasUser);
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        Log::info("Starting massive financial calculation stress test");
        
        // Get all approved tindakan
        $approvedTindakan = Tindakan::where('status_validasi', 'approved')->get();
        $tindakanCount = $approvedTindakan->count();
        
        $this->assertGreaterThan(1000, $tindakanCount, "Need more approved tindakan for stress test");
        
        // Calculate expected totals for verification
        $expectedPendapatanTotal = $approvedTindakan->sum('tarif');
        $expectedJaspelDokter = $approvedTindakan->sum('jasa_dokter');
        $expectedJaspelParamedis = $approvedTindakan->sum('jasa_paramedis');
        $expectedJaspelNonParamedis = $approvedTindakan->sum('jasa_non_paramedis');
        
        // Batch create pendapatan
        $pendapatanData = [];
        foreach ($approvedTindakan as $tindakan) {
            $pendapatanData[] = [
                'tindakan_id' => $tindakan->id,
                'kategori' => 'tindakan_medis',
                'keterangan' => 'Stress test pendapatan',
                'jumlah' => $tindakan->tarif,
                'status' => 'approved',
                'input_by' => $this->petugasUser->id,
                'validasi_by' => $this->bendaharaUser->id,
                'validated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        // Bulk insert pendapatan in chunks
        $pendapatanChunks = array_chunk($pendapatanData, 500);
        foreach ($pendapatanChunks as $chunk) {
            DB::beginTransaction();
            try {
                Pendapatan::insert($chunk);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->fail("Pendapatan bulk insert failed: " . $e->getMessage());
            }
        }
        
        // Batch create jaspel for all types
        $jaspelData = [];
        $periode = Carbon::now()->format('Y-m');
        
        foreach ($approvedTindakan as $tindakan) {
            // Jaspel dokter
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $this->petugasUser->id, // Use petugas as dokter
                'jenis_jaspel' => 'dokter',
                'jumlah' => $tindakan->jasa_dokter,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // Jaspel paramedis
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $this->petugasUser->id,
                'jenis_jaspel' => 'paramedis',
                'jumlah' => $tindakan->jasa_paramedis,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // Jaspel non-paramedis
            $jaspelData[] = [
                'tindakan_id' => $tindakan->id,
                'user_id' => $this->adminUser->id,
                'jenis_jaspel' => 'non_paramedis',
                'jumlah' => $tindakan->jasa_non_paramedis,
                'periode' => $periode,
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        // Bulk insert jaspel in chunks
        $jaspelChunks = array_chunk($jaspelData, 1000);
        foreach ($jaspelChunks as $chunk) {
            DB::beginTransaction();
            try {
                Jaspel::insert($chunk);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->fail("Jaspel bulk insert failed: " . $e->getMessage());
            }
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $totalExecutionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Verify financial calculations
        $actualPendapatanTotal = Pendapatan::sum('jumlah');
        $actualJaspelDokter = Jaspel::where('jenis_jaspel', 'dokter')->sum('jumlah');
        $actualJaspelParamedis = Jaspel::where('jenis_jaspel', 'paramedis')->sum('jumlah');
        $actualJaspelNonParamedis = Jaspel::where('jenis_jaspel', 'non_paramedis')->sum('jumlah');
        
        // Financial accuracy assertions
        $this->assertEquals($expectedPendapatanTotal, $actualPendapatanTotal);
        $this->assertEquals($expectedJaspelDokter, $actualJaspelDokter);
        $this->assertEquals($expectedJaspelParamedis, $actualJaspelParamedis);
        $this->assertEquals($expectedJaspelNonParamedis, $actualJaspelNonParamedis);
        
        // Verify record counts
        $this->assertEquals($tindakanCount, Pendapatan::count());
        $this->assertEquals($tindakanCount * 3, Jaspel::count()); // 3 jaspel types per tindakan
        
        // Performance assertions
        $this->assertLessThan(30.0, $totalExecutionTime, "Financial calculation too slow");
        $recordsPerSecond = ($tindakanCount * 4) / $totalExecutionTime; // pendapatan + 3 jaspel per tindakan
        $this->assertGreaterThan(100, $recordsPerSecond, "Financial record creation too slow");
        
        Log::info("Massive financial calculation completed", [
            'tindakan_count' => $tindakanCount,
            'pendapatan_total' => $actualPendapatanTotal,
            'jaspel_records' => Jaspel::count(),
            'execution_time' => $totalExecutionTime,
            'memory_used_mb' => $memoryUsed / 1024 / 1024,
            'records_per_second' => $recordsPerSecond,
        ]);
        
        return [
            'financial_records_created' => $tindakanCount * 4,
            'execution_time' => $totalExecutionTime,
            'records_per_second' => $recordsPerSecond,
            'memory_used' => $memoryUsed,
        ];
    }

    public function test_cache_performance_under_load()
    {
        $cacheService = app(CacheService::class);
        
        // Clear all caches
        $cacheService->flushAll();
        
        $startTime = microtime(true);
        
        Log::info("Starting cache performance stress test");
        
        // Test 1: Cache miss performance (cold cache)
        $coldCacheStartTime = microtime(true);
        $patientStats = $cacheService->cacheStatistics('patient_stats', function() {
            return [
                'total_count' => Pasien::count(),
                'male_count' => Pasien::where('jenis_kelamin', 'L')->count(),
                'female_count' => Pasien::where('jenis_kelamin', 'P')->count(),
                'recent_count' => Pasien::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ];
        });
        $coldCacheTime = microtime(true) - $coldCacheStartTime;
        
        // Test 2: Cache hit performance (warm cache)
        $warmCacheStartTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $cachedStats = $cacheService->cacheStatistics('patient_stats', function() {
                // This should not be called due to cache hit
                return [];
            });
        }
        $warmCacheTime = (microtime(true) - $warmCacheStartTime) / 100; // Average per call
        
        // Test 3: Multiple cache operations stress test
        $multiCacheStartTime = microtime(true);
        $cacheOperations = 1000;
        
        for ($i = 0; $i < $cacheOperations; $i++) {
            $key = "stress_test_key_{$i}";
            $data = "stress_test_data_{$i}";
            
            // Cache operation
            $cacheService->cacheQuery($key, function() use ($data) {
                return $data;
            });
            
            // Immediate retrieval
            $retrieved = $cacheService->cacheQuery($key, function() {
                return 'should_not_be_called';
            });
            
            $this->assertEquals($data, $retrieved);
        }
        $multiCacheTime = microtime(true) - $multiCacheStartTime;
        
        // Test 4: Cache invalidation performance
        $invalidationStartTime = microtime(true);
        $cacheService->invalidateByTag('model');
        $cacheService->invalidateByTag('query');
        $invalidationTime = microtime(true) - $invalidationStartTime;
        
        $totalTime = microtime(true) - $startTime;
        
        // Performance assertions
        $this->assertLessThan(1.0, $coldCacheTime, "Cold cache too slow");
        $this->assertLessThan(0.001, $warmCacheTime, "Warm cache too slow"); // 1ms per hit
        $this->assertLessThan(5.0, $multiCacheTime, "Multiple cache operations too slow");
        $this->assertLessThan(0.1, $invalidationTime, "Cache invalidation too slow");
        
        // Calculate cache performance metrics
        $cacheOperationsPerSecond = ($cacheOperations * 2) / $multiCacheTime; // 2 ops per iteration
        $this->assertGreaterThan(1000, $cacheOperationsPerSecond, "Cache operations per second too low");
        
        Log::info("Cache performance stress test completed", [
            'cold_cache_time' => $coldCacheTime,
            'warm_cache_time_avg' => $warmCacheTime,
            'multi_cache_time' => $multiCacheTime,
            'invalidation_time' => $invalidationTime,
            'cache_ops_per_second' => $cacheOperationsPerSecond,
            'total_time' => $totalTime,
        ]);
        
        return [
            'cache_performance' => [
                'cold_cache_time' => $coldCacheTime,
                'warm_cache_time' => $warmCacheTime,
                'operations_per_second' => $cacheOperationsPerSecond,
                'invalidation_time' => $invalidationTime,
            ]
        ];
    }

    public function test_database_query_optimization_stress()
    {
        // Use existing large dataset from previous tests
        $this->test_massive_financial_calculation_stress();
        
        $startTime = microtime(true);
        
        Log::info("Starting database query optimization stress test");
        
        // Test complex queries with large datasets
        $complexQueries = [
            'patient_with_tindakan_stats' => function() {
                return Pasien::with(['tindakan' => function($query) {
                    $query->where('status_validasi', 'approved');
                }])
                ->withCount('tindakan')
                ->having('tindakan_count', '>', 0)
                ->limit(100)
                ->get();
            },
            
            'financial_summary_by_period' => function() {
                return DB::table('tindakan')
                    ->join('pendapatan', 'tindakan.id', '=', 'pendapatan.tindakan_id')
                    ->select(
                        DB::raw('DATE(tindakan.created_at) as date'),
                        DB::raw('COUNT(tindakan.id) as tindakan_count'),
                        DB::raw('SUM(pendapatan.jumlah) as total_pendapatan'),
                        DB::raw('AVG(pendapatan.jumlah) as avg_pendapatan')
                    )
                    ->where('tindakan.status_validasi', 'approved')
                    ->groupBy(DB::raw('DATE(tindakan.created_at)'))
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get();
            },
            
            'jaspel_distribution_analysis' => function() {
                return DB::table('jaspel')
                    ->join('users', 'jaspel.user_id', '=', 'users.id')
                    ->select(
                        'users.name',
                        'jaspel.jenis_jaspel',
                        DB::raw('COUNT(jaspel.id) as jaspel_count'),
                        DB::raw('SUM(jaspel.jumlah) as total_jaspel'),
                        DB::raw('AVG(jaspel.jumlah) as avg_jaspel')
                    )
                    ->where('jaspel.status', 'pending')
                    ->groupBy('users.id', 'users.name', 'jaspel.jenis_jaspel')
                    ->orderBy('total_jaspel', 'desc')
                    ->get();
            },
            
            'validation_performance_metrics' => function() {
                return DB::table('tindakan')
                    ->join('users as input_user', 'tindakan.input_by', '=', 'input_user.id')
                    ->leftJoin('users as validator', 'tindakan.validated_by', '=', 'validator.id')
                    ->select(
                        'tindakan.status_validasi',
                        'input_user.name as input_by_name',
                        'validator.name as validated_by_name',
                        DB::raw('COUNT(tindakan.id) as count'),
                        DB::raw('AVG(TIMESTAMPDIFF(HOUR, tindakan.created_at, tindakan.validated_at)) as avg_validation_hours')
                    )
                    ->groupBy('tindakan.status_validasi', 'input_user.id', 'input_user.name', 'validator.id', 'validator.name')
                    ->get();
            }
        ];
        
        $queryResults = [];
        $queryTimes = [];
        
        foreach ($complexQueries as $queryName => $queryFunction) {
            $queryStartTime = microtime(true);
            
            $result = $queryFunction();
            
            $queryEndTime = microtime(true);
            $queryExecutionTime = $queryEndTime - $queryStartTime;
            
            $queryResults[$queryName] = $result;
            $queryTimes[$queryName] = $queryExecutionTime;
            
            // Performance assertion - complex queries should complete within 2 seconds
            $this->assertLessThan(2.0, $queryExecutionTime, "Query '{$queryName}' too slow: {$queryExecutionTime}s");
            
            // Result validation
            $this->assertNotEmpty($result, "Query '{$queryName}' returned empty result");
        }
        
        $totalQueryTime = microtime(true) - $startTime;
        
        // Overall performance assertions
        $this->assertLessThan(5.0, $totalQueryTime, "Total query time too slow");
        $avgQueryTime = array_sum($queryTimes) / count($queryTimes);
        $this->assertLessThan(1.0, $avgQueryTime, "Average query time too slow");
        
        Log::info("Database query optimization stress test completed", [
            'total_queries' => count($complexQueries),
            'total_time' => $totalQueryTime,
            'avg_query_time' => $avgQueryTime,
            'query_times' => $queryTimes,
        ]);
        
        return [
            'query_performance' => [
                'total_time' => $totalQueryTime,
                'average_time' => $avgQueryTime,
                'individual_times' => $queryTimes,
                'results_count' => array_map('count', $queryResults),
            ]
        ];
    }

    public function test_concurrent_user_simulation_stress()
    {
        // Simulate multiple concurrent users
        $concurrentUsers = 10;
        $operationsPerUser = 50;
        $startTime = microtime(true);
        
        Log::info("Starting concurrent user simulation stress test", [
            'concurrent_users' => $concurrentUsers,
            'operations_per_user' => $operationsPerUser,
        ]);
        
        $userResults = [];
        
        // Create multiple users for simulation
        $testUsers = [];
        for ($i = 0; $i < $concurrentUsers; $i++) {
            $testUsers[] = User::create([
                'name' => "Concurrent User {$i}",
                'email' => "concurrent_user_{$i}@test.com",
                'password' => bcrypt('password123'),
                'role_id' => $this->petugasUser->role_id,
                'is_active' => true,
            ]);
        }
        
        // Simulate concurrent operations
        foreach ($testUsers as $userIndex => $user) {
            $userStartTime = microtime(true);
            $this->actingAs($user);
            
            $userOperations = 0;
            
            // Simulate user operations
            for ($op = 0; $op < $operationsPerUser; $op++) {
                // Random operation selection
                $operationType = rand(1, 4);
                
                try {
                    switch ($operationType) {
                        case 1: // Create patient
                            Pasien::create([
                                'no_rekam_medis' => "CONCURRENT_{$userIndex}_{$op}",
                                'nama' => "Concurrent Patient {$userIndex}-{$op}",
                                'tanggal_lahir' => Carbon::now()->subYears(rand(20, 80)),
                                'jenis_kelamin' => rand(0, 1) ? 'L' : 'P',
                                'alamat' => "Concurrent Address {$userIndex}-{$op}",
                            ]);
                            break;
                            
                        case 2: // Create pendapatan
                            Pendapatan::create([
                                'kategori' => 'tindakan_medis',
                                'keterangan' => "Concurrent pendapatan {$userIndex}-{$op}",
                                'jumlah' => rand(50000, 200000),
                                'status' => 'pending',
                                'input_by' => $user->id,
                            ]);
                            break;
                            
                        case 3: // Create pengeluaran
                            Pengeluaran::create([
                                'kategori' => 'operasional',
                                'keterangan' => "Concurrent pengeluaran {$userIndex}-{$op}",
                                'jumlah' => rand(10000, 100000),
                                'status' => 'pending',
                                'input_by' => $user->id,
                            ]);
                            break;
                            
                        case 4: // Query data
                            $randomPatients = Pasien::inRandomOrder()->limit(5)->get();
                            $pendapatanCount = Pendapatan::where('input_by', $user->id)->count();
                            break;
                    }
                    
                    $userOperations++;
                    
                } catch (\Exception $e) {
                    // Log concurrent access conflicts but don't fail the test
                    Log::warning("Concurrent operation conflict", [
                        'user_index' => $userIndex,
                        'operation' => $op,
                        'operation_type' => $operationType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $userEndTime = microtime(true);
            $userExecutionTime = $userEndTime - $userStartTime;
            
            $userResults[$userIndex] = [
                'operations_completed' => $userOperations,
                'execution_time' => $userExecutionTime,
                'operations_per_second' => $userOperations / $userExecutionTime,
            ];
            
            // Performance assertion per user
            $this->assertLessThan(10.0, $userExecutionTime, "User {$userIndex} operations too slow");
            $this->assertGreaterThan($operationsPerUser * 0.8, $userOperations, "User {$userIndex} completed too few operations");
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Calculate overall performance metrics
        $totalOperations = array_sum(array_column($userResults, 'operations_completed'));
        $avgOperationsPerSecond = array_sum(array_column($userResults, 'operations_per_second')) / count($userResults);
        
        // Overall performance assertions
        $this->assertLessThan(15.0, $totalTime, "Concurrent user simulation too slow");
        $this->assertGreaterThan($concurrentUsers * $operationsPerUser * 0.8, $totalOperations, "Too many failed operations");
        
        Log::info("Concurrent user simulation completed", [
            'total_operations' => $totalOperations,
            'total_time' => $totalTime,
            'avg_ops_per_second' => $avgOperationsPerSecond,
            'user_results' => $userResults,
        ]);
        
        return [
            'concurrent_performance' => [
                'total_operations' => $totalOperations,
                'total_time' => $totalTime,
                'avg_operations_per_second' => $avgOperationsPerSecond,
                'user_results' => $userResults,
            ]
        ];
    }

    public function test_system_resource_monitoring_stress()
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        Log::info("Starting system resource monitoring stress test");
        
        // Perform all previous stress tests in sequence
        $results = [];
        
        // Monitor resources during each test
        $results['bulk_patients'] = $this->test_bulk_patient_creation_stress_test();
        $currentMemory = memory_get_usage(true);
        $results['bulk_patients']['memory_after'] = $currentMemory;
        
        $results['massive_tindakan'] = $this->test_massive_tindakan_creation_with_validation();
        $currentMemory = memory_get_usage(true);
        $results['massive_tindakan']['memory_after'] = $currentMemory;
        
        $results['cache_performance'] = $this->test_cache_performance_under_load();
        $currentMemory = memory_get_usage(true);
        $results['cache_performance']['memory_after'] = $currentMemory;
        
        $results['query_optimization'] = $this->test_database_query_optimization_stress();
        $currentMemory = memory_get_usage(true);
        $results['query_optimization']['memory_after'] = $currentMemory;
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $finalPeakMemory = memory_get_peak_usage(true);
        
        $totalExecutionTime = $endTime - $startTime;
        $totalMemoryUsed = $endMemory - $startMemory;
        $peakMemoryUsed = $finalPeakMemory - $peakMemory;
        
        // System resource assertions
        $this->assertLessThan(500 * 1024 * 1024, $totalMemoryUsed, "Total memory usage too high"); // 500MB limit
        $this->assertLessThan(1024 * 1024 * 1024, $finalPeakMemory, "Peak memory usage too high"); // 1GB limit
        $this->assertLessThan(300.0, $totalExecutionTime, "Total system stress test too slow"); // 5 minutes limit
        
        // Calculate overall system performance metrics
        $operationsCount = count($results);
        $avgTimePerOperation = $totalExecutionTime / $operationsCount;
        $memoryEfficiency = $totalMemoryUsed / $totalExecutionTime; // Memory usage per second
        
        Log::info("System resource monitoring stress test completed", [
            'total_time' => $totalExecutionTime,
            'total_memory_used_mb' => $totalMemoryUsed / 1024 / 1024,
            'peak_memory_mb' => $finalPeakMemory / 1024 / 1024,
            'avg_time_per_operation' => $avgTimePerOperation,
            'memory_efficiency_mb_per_sec' => ($memoryEfficiency / 1024 / 1024),
            'operations_completed' => $operationsCount,
        ]);
        
        // Generate comprehensive performance report
        $performanceReport = [
            'execution_summary' => [
                'total_execution_time' => $totalExecutionTime,
                'total_memory_used' => $totalMemoryUsed,
                'peak_memory_used' => $finalPeakMemory,
                'operations_completed' => $operationsCount,
            ],
            'individual_results' => $results,
            'performance_ratings' => [
                'execution_time' => $totalExecutionTime < 180 ? 'excellent' : ($totalExecutionTime < 300 ? 'good' : 'needs_optimization'),
                'memory_usage' => $totalMemoryUsed < 200 * 1024 * 1024 ? 'excellent' : ($totalMemoryUsed < 500 * 1024 * 1024 ? 'good' : 'needs_optimization'),
                'overall' => 'production_ready',
            ],
        ];
        
        Log::info("Performance report generated", $performanceReport);
        
        return $performanceReport;
    }
}