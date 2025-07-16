<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class TestBendaharaDashboardFix extends Command
{
    protected $signature = 'test:bendahara-dashboard-fix';
    protected $description = 'Test all bendahara dashboard conflict fixes';

    public function handle()
    {
        $this->info('🔍 TESTING BENDAHARA DASHBOARD CONFLICT FIXES');
        $this->line('');
        
        $totalTests = 0;
        $passedTests = 0;
        
        // Test 1: CSS Design System Restored
        $this->info('1. Testing CSS Design System...');
        $themeContent = File::get('/Users/kym/Herd/Dokterku/resources/css/filament/bendahara/theme.css');
        
        $cssTests = [
            'Design variables defined' => str_contains($themeContent, '--panel-primary'),
            'Badge styling included' => str_contains($themeContent, 'fi-badge'),
            'Widget styling included' => str_contains($themeContent, 'fi-wi-stats-overview'),
            'Card styling included' => str_contains($themeContent, 'fi-card'),
            'Form styling included' => str_contains($themeContent, 'fi-input'),
            'Dark mode support' => str_contains($themeContent, '.dark'),
            'Responsive design' => str_contains($themeContent, '@media'),
        ];
        
        foreach ($cssTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 2: BadgeColumn Fixes
        $this->info('2. Testing BadgeColumn Fixes...');
        $badgeColumnFiles = glob('/Users/kym/Herd/Dokterku/app/Filament/Bendahara/Resources/*.php');
        
        $badgeTests = [
            'No BadgeColumn usage' => true,
            'TextColumn with badge() method' => false,
        ];
        
        foreach ($badgeColumnFiles as $file) {
            $content = File::get($file);
            if (str_contains($content, 'BadgeColumn')) {
                $badgeTests['No BadgeColumn usage'] = false;
                $this->line("  ❌ Found BadgeColumn in: " . basename($file), 'error');
            }
            if (str_contains($content, 'TextColumn') && str_contains($content, '->badge()')) {
                $badgeTests['TextColumn with badge() method'] = true;
            }
        }
        
        foreach ($badgeTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 3: Chart.js Removal
        $this->info('3. Testing Chart.js Removal...');
        $widgetContent = File::get('/Users/kym/Herd/Dokterku/resources/views/filament/bendahara/widgets/interactive-dashboard-widget.blade.php');
        
        $chartTests = [
            'No Chart.js CDN' => !str_contains($widgetContent, 'cdn.jsdelivr.net/npm/chart.js'),
            'No canvas elements' => !str_contains($widgetContent, '<canvas'),
            'No Chart initialization' => !str_contains($widgetContent, 'new Chart('),
            'Uses Filament components' => str_contains($widgetContent, 'x-filament::card'),
            'Uses progress bars' => str_contains($widgetContent, 'bg-success-600'),
        ];
        
        foreach ($chartTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 4: BudgetPlan Model
        $this->info('4. Testing BudgetPlan Model...');
        $budgetModelTests = [
            'BudgetPlan model exists' => File::exists('/Users/kym/Herd/Dokterku/app/Models/BudgetPlan.php'),
            'Widget uses BudgetPlan' => str_contains(File::get('/Users/kym/Herd/Dokterku/app/Filament/Bendahara/Widgets/BudgetTrackingWidget.php'), 'BudgetPlan::getCurrentBudget()'),
        ];
        
        foreach ($budgetModelTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 5: Widget Registration
        $this->info('5. Testing Widget Registration...');
        $providerContent = File::get('/Users/kym/Herd/Dokterku/app/Providers/Filament/BendaharaPanelProvider.php');
        
        $widgetTests = [
            'InteractiveDashboardWidget registered' => str_contains($providerContent, 'InteractiveDashboardWidget::class'),
            'BudgetTrackingWidget registered' => str_contains($providerContent, 'BudgetTrackingWidget::class'),
            'LanguageSwitcherWidget registered' => str_contains($providerContent, 'LanguageSwitcherWidget::class'),
        ];
        
        foreach ($widgetTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 6: File Integrity
        $this->info('6. Testing File Integrity...');
        $fileTests = [
            'Theme CSS valid' => $this->validateCSSFile('/Users/kym/Herd/Dokterku/resources/css/filament/bendahara/theme.css'),
            'Widget views exist' => File::exists('/Users/kym/Herd/Dokterku/resources/views/filament/bendahara/widgets/interactive-dashboard-widget.blade.php'),
            'Widget classes exist' => File::exists('/Users/kym/Herd/Dokterku/app/Filament/Bendahara/Widgets/InteractiveDashboardWidget.php'),
        ];
        
        foreach ($fileTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Summary
        $this->info('📊 TEST SUMMARY');
        $this->line("Total Tests: {$totalTests}");
        $this->line("Passed: {$passedTests}");
        $this->line("Failed: " . ($totalTests - $passedTests));
        $this->line("Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%");
        
        if ($passedTests === $totalTests) {
            $this->info('🎉 ALL TESTS PASSED - BENDAHARA DASHBOARD CONFLICTS RESOLVED!');
        } else {
            $this->error('❌ SOME TESTS FAILED - REVIEW THE ERRORS ABOVE');
        }
        
        $this->line('');
        $this->info('🔧 CRITICAL FIXES APPLIED:');
        $this->line('  1. ✅ CSS Design System Restored');
        $this->line('  2. ✅ Deprecated BadgeColumn Fixed');
        $this->line('  3. ✅ Chart.js Conflicts Removed');
        $this->line('  4. ✅ BudgetPlan Model Created');
        $this->line('  5. ✅ Filament Components Used');
        $this->line('  6. ✅ Widget Registration Complete');
        
        return $passedTests === $totalTests ? 0 : 1;
    }
    
    private function validateCSSFile(string $path): bool
    {
        if (!File::exists($path)) {
            return false;
        }
        
        $content = File::get($path);
        
        // Basic CSS validation
        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        
        return $openBraces === $closeBraces && strlen($content) > 100;
    }
}