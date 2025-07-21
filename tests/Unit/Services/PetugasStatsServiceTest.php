<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\PetugasStatsService;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Carbon\Carbon;

class PetugasStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PetugasStatsService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new PetugasStatsService();
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_it_calculates_daily_stats_correctly()
    {
        // Arrange
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Create test data for today
        Pasien::factory()->count(5)->create([
            'input_by' => $this->user->id,
            'created_at' => $today,
        ]);
        
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Test Pendapatan']);
        PendapatanHarian::factory()->create([
            'user_id' => $this->user->id,
            'tanggal_input' => $today->format('Y-m-d'),
            'nominal' => 100000,
            'pendapatan_id' => $pendapatan->id,
        ]);
        
        $pengeluaran = Pengeluaran::factory()->create(['nama_pengeluaran' => 'Test Pengeluaran']);
        PengeluaranHarian::factory()->create([
            'user_id' => $this->user->id,
            'tanggal_input' => $today->format('Y-m-d'),
            'nominal' => 50000,
            'pengeluaran_id' => $pengeluaran->id,
        ]);
        
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        Tindakan::factory()->count(3)->create([
            'input_by' => $this->user->id,
            'tanggal_tindakan' => $today,
            'jenis_tindakan_id' => $jenisTindakan->id,
            'tarif' => 75000,
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('today', $stats['daily']);
        
        $todayStats = $stats['daily']['today'];
        $this->assertEquals(5, $todayStats['pasien_count']);
        $this->assertEquals(100000, $todayStats['pendapatan_sum']);
        $this->assertEquals(50000, $todayStats['pengeluaran_sum']);
        $this->assertEquals(3, $todayStats['tindakan_count']);
        $this->assertEquals(50000, $todayStats['net_income']); // 100000 - 50000
    }

    public function test_it_handles_cache_efficiently()
    {
        // Arrange
        $cacheKey = "petugas_stats_{$this->user->id}";
        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, \Mockery::any(), \Mockery::any())
            ->andReturn([
                'daily' => ['today' => ['pasien_count' => 10]],
                'monthly' => ['this_month' => ['pasien_count' => 100]],
                'trends' => ['last_7_days' => []],
                'validation_summary' => ['pending_validations' => 5],
                'performance_metrics' => ['monthly_target' => 100],
            ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('daily', $stats);
        $this->assertEquals(10, $stats['daily']['today']['pasien_count']);
    }

    public function test_it_calculates_trends_accurately()
    {
        // Arrange
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Clear cache to ensure fresh data
        Cache::flush();
        
        // Create more data for today than yesterday
        $todayPatients = Pasien::factory()->count(5)->create([
            'input_by' => $this->user->id,
            'created_at' => $today,
        ]);
        
        $yesterdayPatients = Pasien::factory()->count(3)->create([
            'input_by' => $this->user->id,
            'created_at' => $yesterday,
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('trends', $stats['daily']);
        
        $trends = $stats['daily']['trends'];
        
        $this->assertArrayHasKey('pasien_count', $trends);
        $this->assertArrayHasKey('current', $trends['pasien_count']);
        $this->assertArrayHasKey('previous', $trends['pasien_count']);
        $this->assertArrayHasKey('direction', $trends['pasien_count']);
        $this->assertArrayHasKey('percentage', $trends['pasien_count']);
        
        $this->assertEquals(5, $trends['pasien_count']['current']);
        $this->assertEquals(3, $trends['pasien_count']['previous']);
        $this->assertEquals('up', $trends['pasien_count']['direction']);
        $this->assertGreaterThan(0, $trends['pasien_count']['percentage']);
    }

    public function test_it_handles_missing_data_gracefully()
    {
        // Arrange - No test data created
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('daily', $stats);
        $todayStats = $stats['daily']['today'];
        
        $this->assertEquals(0, $todayStats['pasien_count']);
        $this->assertEquals(0, $todayStats['pendapatan_sum']);
        $this->assertEquals(0, $todayStats['pengeluaran_sum']);
        $this->assertEquals(0, $todayStats['tindakan_count']);
        $this->assertEquals(0, $todayStats['net_income']);
    }

    public function test_it_optimizes_database_queries()
    {
        // Arrange
        $today = Carbon::today();
        
        // Create test data
        Pasien::factory()->count(3)->create([
            'input_by' => $this->user->id,
            'created_at' => $today,
        ]);
        
        // Act & Assert - Check that bulk query method is used
        DB::enableQueryLog();
        $stats = $this->service->getDashboardStats($this->user->id);
        $queries = DB::getQueryLog();
        
        // Should use optimized bulk queries instead of individual queries
        $this->assertLessThan(10, count($queries), 'Should use optimized bulk queries');
        
        // Check that trend analysis uses bulk operations
        $this->assertArrayHasKey('trends', $stats);
        $this->assertArrayHasKey('charts', $stats['trends']);
    }

    public function test_it_formats_statistics_for_display()
    {
        // Arrange
        $today = Carbon::today();
        
        $pendapatan = Pendapatan::factory()->create(['nama_pendapatan' => 'Test Pendapatan']);
        PendapatanHarian::factory()->create([
            'user_id' => $this->user->id,
            'tanggal_input' => $today->format('Y-m-d'),
            'nominal' => 1500000, // 1.5 million
            'pendapatan_id' => $pendapatan->id,
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('daily', $stats);
        $todayStats = $stats['daily']['today'];
        
        $this->assertIsFloat($todayStats['pendapatan_sum']);
        $this->assertEquals(1500000, $todayStats['pendapatan_sum']);
        $this->assertArrayHasKey('date', $todayStats);
        $this->assertEquals($today->format('Y-m-d'), $todayStats['date']);
    }

    public function test_it_handles_monthly_statistics()
    {
        // Arrange
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Create data for this month
        Pasien::factory()->count(10)->create([
            'input_by' => $this->user->id,
            'created_at' => $thisMonth->addDays(5),
        ]);
        
        // Create data for last month
        Pasien::factory()->count(8)->create([
            'input_by' => $this->user->id,
            'created_at' => $lastMonth->addDays(10),
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('monthly', $stats);
        $monthlyStats = $stats['monthly'];
        
        $this->assertArrayHasKey('this_month', $monthlyStats);
        $this->assertArrayHasKey('last_month', $monthlyStats);
        $this->assertArrayHasKey('trends', $monthlyStats);
        
        $this->assertEquals(10, $monthlyStats['this_month']['pasien_count']);
        $this->assertEquals(8, $monthlyStats['last_month']['pasien_count']);
    }

    public function test_it_calculates_validation_summary()
    {
        // Arrange
        $jenisTindakan = JenisTindakan::factory()->create(['nama' => 'Test Tindakan']);
        
        // Create pending validations
        Tindakan::factory()->count(3)->create([
            'input_by' => $this->user->id,
            'status_validasi' => 'pending',
            'jenis_tindakan_id' => $jenisTindakan->id,
        ]);
        
        // Create approved validations
        Tindakan::factory()->count(2)->create([
            'input_by' => $this->user->id,
            'status_validasi' => 'approved',
            'approved_at' => Carbon::today(),
            'jenis_tindakan_id' => $jenisTindakan->id,
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('validation_summary', $stats);
        $validationSummary = $stats['validation_summary'];
        
        $this->assertEquals(3, $validationSummary['pending_validations']);
        $this->assertEquals(2, $validationSummary['approved_today']);
        $this->assertEquals(100, $validationSummary['approval_rate']); // 2/2 = 100%
    }

    public function test_it_calculates_performance_metrics()
    {
        // Arrange
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Create patients for this month
        Pasien::factory()->count(25)->create([
            'input_by' => $this->user->id,
            'created_at' => $thisMonth->addDays(10),
        ]);
        
        // Act
        $stats = $this->service->getDashboardStats($this->user->id);
        
        // Assert
        $this->assertArrayHasKey('performance_metrics', $stats);
        $performanceMetrics = $stats['performance_metrics'];
        
        $this->assertArrayHasKey('monthly_target', $performanceMetrics);
        $this->assertArrayHasKey('current_achievement', $performanceMetrics);
        $this->assertArrayHasKey('completion_rate', $performanceMetrics);
        
        $this->assertEquals(25, $performanceMetrics['current_achievement']);
        $this->assertEquals(25, $performanceMetrics['completion_rate']); // 25/100 = 25%
    }

    public function test_it_clears_stats_cache()
    {
        // Arrange
        $cacheKey = "petugas_stats_{$this->user->id}";
        Cache::put($cacheKey, ['test' => 'data'], 60);
        
        // Act
        $this->service->clearStatsCache($this->user->id);
        
        // Assert
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_it_handles_authentication_errors()
    {
        // Arrange
        Auth::logout();
        
        // Act
        $stats = $this->service->getDashboardStats();
        
        // Assert - Should handle missing authentication gracefully
        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('monthly', $stats);
        $this->assertArrayHasKey('trends', $stats);
        $this->assertArrayHasKey('validation_summary', $stats);
        $this->assertArrayHasKey('performance_metrics', $stats);
    }

    public function test_it_uses_bulk_operations_for_trend_analysis()
    {
        // Arrange
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays($i);
            $dates[] = $date;
            
            Pasien::factory()->count(2)->create([
                'input_by' => $this->user->id,
                'created_at' => $date,
            ]);
        }
        
        // Act
        DB::enableQueryLog();
        $stats = $this->service->getDashboardStats($this->user->id);
        $queries = DB::getQueryLog();
        
        // Assert
        $this->assertArrayHasKey('trends', $stats);
        $this->assertArrayHasKey('charts', $stats['trends']);
        
        $charts = $stats['trends']['charts'];
        $this->assertArrayHasKey('daily_patients', $charts);
        $this->assertArrayHasKey('daily_income', $charts);
        $this->assertArrayHasKey('daily_treatments', $charts);
        
        // Should use bulk queries instead of individual queries per day
        $this->assertLessThan(20, count($queries), 'Should use bulk queries for trend analysis');
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        Cache::flush();
        parent::tearDown();
    }
}