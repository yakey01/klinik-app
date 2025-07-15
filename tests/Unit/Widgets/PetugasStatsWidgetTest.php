<?php

namespace Tests\Unit\Widgets;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Filament\Petugas\Widgets\PetugasStatsWidget;
use App\Services\PetugasStatsService;
use App\Models\User;
use Mockery;

class PetugasStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected PetugasStatsWidget $widget;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        $this->widget = new PetugasStatsWidget();
    }

    public function test_it_displays_correct_statistics_format()
    {
        // Arrange
        $mockStatsService = Mockery::mock(PetugasStatsService::class);
        $mockStatsService->shouldReceive('getDashboardStats')
            ->with($this->user->id)
            ->andReturn([
                'daily' => [
                    'today' => [
                        'pasien_count' => 15,
                        'pendapatan_sum' => 1500000,
                        'pengeluaran_sum' => 500000,
                        'tindakan_count' => 8,
                        'net_income' => 1000000,
                    ],
                    'trends' => [
                        'pasien_count' => [
                            'direction' => 'up',
                            'percentage' => 25.5,
                        ],
                        'pendapatan_sum' => [
                            'direction' => 'up',
                            'percentage' => 15.2,
                        ],
                        'pengeluaran_sum' => [
                            'direction' => 'down',
                            'percentage' => 10.0,
                        ],
                        'tindakan_count' => [
                            'direction' => 'stable',
                            'percentage' => 0.0,
                        ],
                        'net_income' => [
                            'direction' => 'up',
                            'percentage' => 33.3,
                        ],
                    ],
                ],
                'validation_summary' => [
                    'pending_validations' => 5,
                    'approval_rate' => 85.5,
                    'approved_today' => 3,
                    'rejected_today' => 1,
                ],
                'trends' => [
                    'charts' => [
                        'daily_patients' => [10, 12, 15, 13, 16, 14, 15],
                        'daily_income' => [800000, 900000, 1000000, 1100000, 1200000, 1000000, 1500000],
                        'daily_treatments' => [5, 6, 8, 7, 9, 6, 8],
                    ],
                ],
            ]);
        
        // Inject mock service
        $this->app->instance(PetugasStatsService::class, $mockStatsService);
        
        // Act
        $stats = $this->widget->getStats();
        
        // Assert
        $this->assertIsArray($stats);
        $this->assertCount(6, $stats); // Should have 6 stat cards
        
        // Check patient stat
        $patientStat = $stats[0];
        $this->assertEquals('👥 Pasien Hari Ini', $patientStat->getLabel());
        $this->assertEquals(15, $patientStat->getValue());
        $this->assertEquals('success', $patientStat->getColor());
        
        // Check income stat
        $incomeStat = $stats[1];
        $this->assertEquals('💰 Pendapatan Hari Ini', $incomeStat->getLabel());
        $this->assertEquals('Rp 1.500.000', $incomeStat->getValue());
        $this->assertEquals('success', $incomeStat->getColor());
        
        // Check expense stat
        $expenseStat = $stats[2];
        $this->assertEquals('💸 Pengeluaran Hari Ini', $expenseStat->getLabel());
        $this->assertEquals('Rp 500.000', $expenseStat->getValue());
        $this->assertEquals('success', $expenseStat->getColor()); // Down trend is good for expenses
        
        // Check treatment stat
        $treatmentStat = $stats[3];
        $this->assertEquals('🏥 Tindakan Hari Ini', $treatmentStat->getLabel());
        $this->assertEquals(8, $treatmentStat->getValue());
        $this->assertEquals('gray', $treatmentStat->getColor()); // Stable trend
        
        // Check net income stat
        $netIncomeStat = $stats[4];
        $this->assertEquals('📊 Net Hari Ini', $netIncomeStat->getLabel());
        $this->assertEquals('Rp 1.000.000', $netIncomeStat->getValue());
        $this->assertEquals('success', $netIncomeStat->getColor());
        
        // Check validation stat
        $validationStat = $stats[5];
        $this->assertEquals('📋 Validasi Pending', $validationStat->getLabel());
        $this->assertEquals(5, $validationStat->getValue());
        $this->assertEquals('warning', $validationStat->getColor()); // > 10 would be warning
    }

    public function test_it_handles_empty_data_gracefully()
    {
        // Arrange
        $mockStatsService = Mockery::mock(PetugasStatsService::class);
        $mockStatsService->shouldReceive('getDashboardStats')
            ->with($this->user->id)
            ->andReturn([
                'daily' => [
                    'today' => [
                        'pasien_count' => 0,
                        'pendapatan_sum' => 0,
                        'pengeluaran_sum' => 0,
                        'tindakan_count' => 0,
                        'net_income' => 0,
                    ],
                    'trends' => [
                        'pasien_count' => ['direction' => 'stable', 'percentage' => 0.0],
                        'pendapatan_sum' => ['direction' => 'stable', 'percentage' => 0.0],
                        'pengeluaran_sum' => ['direction' => 'stable', 'percentage' => 0.0],
                        'tindakan_count' => ['direction' => 'stable', 'percentage' => 0.0],
                        'net_income' => ['direction' => 'stable', 'percentage' => 0.0],
                    ],
                ],
                'validation_summary' => [
                    'pending_validations' => 0,
                    'approval_rate' => 0,
                    'approved_today' => 0,
                    'rejected_today' => 0,
                ],
                'trends' => [
                    'charts' => [
                        'daily_patients' => [0, 0],
                        'daily_income' => [0, 0],
                        'daily_treatments' => [0, 0],
                    ],
                ],
            ]);
        
        // Inject mock service
        $this->app->instance(PetugasStatsService::class, $mockStatsService);
        
        // Act
        $stats = $this->widget->getStats();
        
        // Assert
        $this->assertIsArray($stats);
        $this->assertCount(6, $stats);
        
        // Check that all stats show zero values
        $this->assertEquals(0, $stats[0]->getValue());
        $this->assertEquals('Rp 0', $stats[1]->getValue());
        $this->assertEquals('Rp 0', $stats[2]->getValue());
        $this->assertEquals(0, $stats[3]->getValue());
        $this->assertEquals('Rp 0', $stats[4]->getValue());
        $this->assertEquals(0, $stats[5]->getValue());
        
        // Check that all trends show stable
        foreach ($stats as $stat) {
            $this->assertEquals('heroicon-m-minus', $stat->getDescriptionIcon());
            $this->assertEquals('gray', $stat->getColor());
        }
    }

    public function test_it_shows_proper_trend_indicators()
    {
        // Test up trend
        $upTrend = $this->widget->getTrendIcon('up');
        $this->assertEquals('heroicon-m-arrow-trending-up', $upTrend);
        
        // Test down trend
        $downTrend = $this->widget->getTrendIcon('down');
        $this->assertEquals('heroicon-m-arrow-trending-down', $downTrend);
        
        // Test stable trend
        $stableTrend = $this->widget->getTrendIcon('stable');
        $this->assertEquals('heroicon-m-minus', $stableTrend);
        
        // Test default trend
        $defaultTrend = $this->widget->getTrendIcon('unknown');
        $this->assertEquals('heroicon-m-minus', $defaultTrend);
    }

    public function test_it_shows_proper_trend_colors()
    {
        // Test up trend color
        $upColor = $this->widget->getTrendColor('up');
        $this->assertEquals('success', $upColor);
        
        // Test down trend color
        $downColor = $this->widget->getTrendColor('down');
        $this->assertEquals('warning', $downColor);
        
        // Test stable trend color
        $stableColor = $this->widget->getTrendColor('stable');
        $this->assertEquals('gray', $stableColor);
        
        // Test default trend color
        $defaultColor = $this->widget->getTrendColor('unknown');
        $this->assertEquals('gray', $defaultColor);
    }

    public function test_it_shows_proper_expense_trend_colors()
    {
        // For expenses, down is good (less spending)
        $upColor = $this->widget->getExpenseTrendColor('up');
        $this->assertEquals('danger', $upColor);
        
        $downColor = $this->widget->getExpenseTrendColor('down');
        $this->assertEquals('success', $downColor);
        
        $stableColor = $this->widget->getExpenseTrendColor('stable');
        $this->assertEquals('gray', $stableColor);
        
        $defaultColor = $this->widget->getExpenseTrendColor('unknown');
        $this->assertEquals('gray', $defaultColor);
    }

    public function test_it_formats_trend_descriptions_correctly()
    {
        // Test up trend description
        $upTrend = ['direction' => 'up', 'percentage' => 25.5];
        $upDescription = $this->widget->getTrendDescription($upTrend);
        $this->assertEquals('+25.5% dari kemarin', $upDescription);
        
        // Test down trend description
        $downTrend = ['direction' => 'down', 'percentage' => 15.2];
        $downDescription = $this->widget->getTrendDescription($downTrend);
        $this->assertEquals('-15.2% dari kemarin', $downDescription);
        
        // Test stable trend description
        $stableTrend = ['direction' => 'stable', 'percentage' => 0.0];
        $stableDescription = $this->widget->getTrendDescription($stableTrend);
        $this->assertEquals('Tidak ada perubahan', $stableDescription);
    }

    public function test_it_processes_chart_data_correctly()
    {
        // Test with empty data
        $emptyChart = $this->widget->getChartData([]);
        $this->assertEquals([0, 0], $emptyChart);
        
        // Test with single data point
        $singleChart = $this->widget->getChartData([100]);
        $this->assertEquals([0, 100], $singleChart);
        
        // Test with multiple data points
        $multipleChart = $this->widget->getChartData([10, 20, 30, 40, 50, 60, 70, 80, 90]);
        $this->assertEquals([30, 40, 50, 60, 70, 80, 90], $multipleChart); // Last 7 days
        
        // Test with exactly 7 data points
        $sevenChart = $this->widget->getChartData([10, 20, 30, 40, 50, 60, 70]);
        $this->assertEquals([10, 20, 30, 40, 50, 60, 70], $sevenChart);
    }

    public function test_it_handles_authentication_errors()
    {
        // Arrange
        Auth::logout();
        
        // Act
        $stats = $this->widget->getStats();
        
        // Assert - Should return empty stats when no user is authenticated
        $this->assertIsArray($stats);
        $this->assertCount(6, $stats);
        
        // All stats should show zero values
        foreach ($stats as $stat) {
            $this->assertContains($stat->getValue(), [0, 'Rp 0']);
            $this->assertEquals('gray', $stat->getColor());
        }
    }

    public function test_it_handles_service_errors_gracefully()
    {
        // Arrange
        $mockStatsService = Mockery::mock(PetugasStatsService::class);
        $mockStatsService->shouldReceive('getDashboardStats')
            ->with($this->user->id)
            ->andThrow(new \Exception('Service error'));
        
        // Inject mock service
        $this->app->instance(PetugasStatsService::class, $mockStatsService);
        
        // Act
        $stats = $this->widget->getStats();
        
        // Assert - Should return empty stats when service fails
        $this->assertIsArray($stats);
        $this->assertCount(6, $stats);
        
        // All stats should show zero values
        foreach ($stats as $stat) {
            $this->assertContains($stat->getValue(), [0, 'Rp 0']);
            $this->assertEquals('gray', $stat->getColor());
        }
    }

    public function test_it_has_correct_polling_interval()
    {
        // Act
        $pollingInterval = $this->widget::getPollingInterval();
        
        // Assert
        $this->assertEquals('60s', $pollingInterval);
    }

    public function test_it_formats_numbers_correctly()
    {
        // Arrange
        $mockStatsService = Mockery::mock(PetugasStatsService::class);
        $mockStatsService->shouldReceive('getDashboardStats')
            ->with($this->user->id)
            ->andReturn([
                'daily' => [
                    'today' => [
                        'pasien_count' => 1000,
                        'pendapatan_sum' => 1234567,
                        'pengeluaran_sum' => 987654,
                        'tindakan_count' => 50,
                        'net_income' => 246913,
                    ],
                    'trends' => [
                        'pasien_count' => ['direction' => 'up', 'percentage' => 25.0],
                        'pendapatan_sum' => ['direction' => 'up', 'percentage' => 15.0],
                        'pengeluaran_sum' => ['direction' => 'down', 'percentage' => 10.0],
                        'tindakan_count' => ['direction' => 'stable', 'percentage' => 0.0],
                        'net_income' => ['direction' => 'up', 'percentage' => 33.0],
                    ],
                ],
                'validation_summary' => [
                    'pending_validations' => 15,
                    'approval_rate' => 87.5,
                    'approved_today' => 7,
                    'rejected_today' => 1,
                ],
                'trends' => [
                    'charts' => [
                        'daily_patients' => [1000, 1100, 1200],
                        'daily_income' => [1000000, 1200000, 1234567],
                        'daily_treatments' => [40, 45, 50],
                    ],
                ],
            ]);
        
        // Inject mock service
        $this->app->instance(PetugasStatsService::class, $mockStatsService);
        
        // Act
        $stats = $this->widget->getStats();
        
        // Assert - Check number formatting
        $this->assertEquals(1000, $stats[0]->getValue());
        $this->assertEquals('Rp 1.234.567', $stats[1]->getValue());
        $this->assertEquals('Rp 987.654', $stats[2]->getValue());
        $this->assertEquals(50, $stats[3]->getValue());
        $this->assertEquals('Rp 246.913', $stats[4]->getValue());
        $this->assertEquals(15, $stats[5]->getValue());
        
        // Check validation count color (should be warning for > 10)
        $this->assertEquals('warning', $stats[5]->getColor());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}