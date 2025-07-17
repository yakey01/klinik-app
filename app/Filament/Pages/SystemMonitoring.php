<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\ThemeMode;
use App\Models\SystemMetric;
use App\Services\SystemMonitoringService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SystemMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static string $view = 'filament.pages.system-monitoring';
    
    protected static ?string $navigationLabel = 'System Monitoring';
    
    protected static ?string $title = 'System Monitoring Dashboard';
    
    protected static ?string $navigationGroup = '⚙️ SYSTEM ADMINISTRATION';
    
    protected static ?int $navigationSort = 5;
    
    protected static ?string $slug = 'system-monitoring';
    
    // Polling interval for real-time updates
    protected static string $pollingInterval = '15s';
    
    // Page properties
    public $healthSummary = [];
    public $systemMetrics = [];
    public $databaseMetrics = [];
    public $cacheMetrics = [];
    public $performanceMetrics = [];
    public $securityMetrics = [];
    public $criticalAlerts = [];
    public $lastUpdate;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }
    
    public function mount(): void
    {
        $this->loadMetrics();
    }
    
    public function loadMetrics(): void
    {
        $this->healthSummary = SystemMetric::getHealthSummary();
        $this->systemMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_SYSTEM, 1);
        $this->databaseMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_DATABASE, 1);
        $this->cacheMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_CACHE, 1);
        $this->performanceMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_PERFORMANCE, 1);
        $this->securityMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_SECURITY, 1);
        $this->criticalAlerts = SystemMetric::getCriticalAlerts();
        $this->lastUpdate = now()->format('Y-m-d H:i:s');
    }
    
    public function refreshMetrics(): void
    {
        $monitoringService = new SystemMonitoringService();
        $success = $monitoringService->collectAllMetrics();
        
        if ($success) {
            $this->loadMetrics();
            Notification::make()
                ->title('Metrics refreshed successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to refresh metrics')
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('refreshMetrics')
                ->keyBindings(['r']),
                
            Action::make('cleanup')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clean up old metrics')
                ->modalDescription('This will delete system metrics older than 30 days.')
                ->action(function () {
                    $deleted = SystemMetric::cleanup(30);
                    Notification::make()
                        ->title("Cleaned up {$deleted} old metrics")
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function getMetricValue($metrics, $name, $default = 'N/A')
    {
        if (isset($metrics[$name]) && $metrics[$name]->isNotEmpty()) {
            return $metrics[$name]->first()->metric_value;
        }
        return $default;
    }
    
    public function getMetricStatus($metrics, $name)
    {
        if (isset($metrics[$name]) && $metrics[$name]->isNotEmpty()) {
            return $metrics[$name]->first()->status;
        }
        return 'unknown';
    }
    
    public function getMetricData($metrics, $name)
    {
        if (isset($metrics[$name]) && $metrics[$name]->isNotEmpty()) {
            return $metrics[$name]->first()->metric_data;
        }
        return [];
    }
    
    public function getStatusColor($status)
    {
        return match($status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }
    
    public function getStatusIcon($status)
    {
        return match($status) {
            'healthy' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'critical' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }
}