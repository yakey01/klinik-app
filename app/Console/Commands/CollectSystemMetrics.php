<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SystemMonitoringService;
use App\Models\SystemMetric;

class CollectSystemMetrics extends Command
{
    protected $signature = 'system:collect-metrics
                            {--cleanup : Clean up old metrics after collection}
                            {--days=30 : Number of days to keep metrics when cleaning up}';

    protected $description = 'Collect system metrics for monitoring dashboard';

    private SystemMonitoringService $monitoringService;

    public function __construct(SystemMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $this->info('Starting system metrics collection...');
        
        $startTime = microtime(true);
        
        try {
            $success = $this->monitoringService->collectAllMetrics();
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            if ($success) {
                $this->info("✓ System metrics collected successfully in {$duration}ms");
                
                // Show summary
                $this->showMetricsSummary();
                
                // Cleanup old metrics if requested
                if ($this->option('cleanup')) {
                    $days = (int) $this->option('days');
                    $this->cleanupOldMetrics($days);
                }
                
                return self::SUCCESS;
            } else {
                $this->error("✗ Failed to collect system metrics");
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("✗ Error collecting metrics: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function showMetricsSummary()
    {
        $summary = SystemMetric::getHealthSummary();
        
        $this->line('');
        $this->line('<fg=cyan>System Health Summary:</fg=cyan>');
        $this->line('├─ Overall Status: ' . $this->getStatusDisplay($summary['overall_status']));
        $this->line('├─ Total Metrics: ' . $summary['total_metrics']);
        $this->line('├─ Healthy: ' . $summary['healthy']);
        $this->line('├─ Warning: ' . $summary['warning']);
        $this->line('└─ Critical: ' . $summary['critical']);
        
        // Show critical alerts if any
        $criticalAlerts = SystemMetric::getCriticalAlerts();
        if ($criticalAlerts->isNotEmpty()) {
            $this->line('');
            $this->line('<fg=red>Critical Alerts:</fg=red>');
            foreach ($criticalAlerts as $alert) {
                $this->line("├─ {$alert->metric_name}: {$alert->metric_value}" . 
                          ($alert->alert_threshold ? " (threshold: {$alert->alert_threshold})" : ''));
            }
        }
    }

    private function getStatusDisplay($status)
    {
        return match($status) {
            'healthy' => '<fg=green>Healthy</fg=green>',
            'warning' => '<fg=yellow>Warning</fg=yellow>',
            'critical' => '<fg=red>Critical</fg=red>',
            default => '<fg=gray>Unknown</fg=gray>',
        };
    }

    private function cleanupOldMetrics($days)
    {
        $this->line('');
        $this->info("Cleaning up metrics older than {$days} days...");
        
        $deleted = SystemMetric::cleanup($days);
        
        if ($deleted > 0) {
            $this->info("✓ Cleaned up {$deleted} old metrics");
        } else {
            $this->info("✓ No old metrics to clean up");
        }
    }
}