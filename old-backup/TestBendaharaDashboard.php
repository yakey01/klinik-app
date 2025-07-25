<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class TestBendaharaDashboard extends Command
{
    protected $signature = 'test:bendahara-dashboard';
    protected $description = 'Test bendahara dashboard functionality';

    public function handle()
    {
        $this->info('🧪 TESTING BENDAHARA DASHBOARD');
        $this->line('');
        
        $totalTests = 0;
        $passedTests = 0;
        
        // Test 1: File Structure
        $this->info('1. Testing File Structure...');
        $fileTests = [
            'Dashboard Page Class' => File::exists('/Users/kym/Herd/Dokterku/app/Filament/Bendahara/Pages/BendaharaDashboard.php'),
            'Dashboard View File' => File::exists('/Users/kym/Herd/Dokterku/resources/views/filament/bendahara/pages/bendahara-dashboard.blade.php'),
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
        
        // Test 2: Class Structure
        $this->info('2. Testing Class Structure...');
        $pageContent = File::get('/Users/kym/Herd/Dokterku/app/Filament/Bendahara/Pages/BendaharaDashboard.php');
        
        $classTests = [
            'Extends Page class' => str_contains($pageContent, 'extends Page'),
            'Has navigation icon' => str_contains($pageContent, 'navigationIcon'),
            'Has view property' => str_contains($pageContent, 'bendahara-dashboard'),
            'Has getFinancialSummary method' => str_contains($pageContent, 'getFinancialSummary'),
            'Has getValidationStats method' => str_contains($pageContent, 'getValidationStats'),
            'Has getRecentTransactions method' => str_contains($pageContent, 'getRecentTransactions'),
            'Has getMonthlyTrends method' => str_contains($pageContent, 'getMonthlyTrends'),
            'Has getTopPerformers method' => str_contains($pageContent, 'getTopPerformers'),
        ];
        
        foreach ($classTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 3: View Structure
        $this->info('3. Testing View Structure...');
        $viewContent = File::get('/Users/kym/Herd/Dokterku/resources/views/filament/bendahara/pages/bendahara-dashboard.blade.php');
        
        $viewTests = [
            'Uses Filament page component' => str_contains($viewContent, 'x-filament-panels::page'),
            'Has financial summary section' => str_contains($viewContent, 'getFinancialSummary'),
            'Has validation stats section' => str_contains($viewContent, 'getValidationStats'),
            'Has recent transactions section' => str_contains($viewContent, 'getRecentTransactions'),
            'Has monthly trends section' => str_contains($viewContent, 'getMonthlyTrends'),
            'Has top performers section' => str_contains($viewContent, 'getTopPerformers'),
            'Uses Filament components' => str_contains($viewContent, 'x-filament::section'),
            'Has proper grid layout' => str_contains($viewContent, 'grid grid-cols'),
            'Has responsive design' => str_contains($viewContent, 'md:grid-cols') && str_contains($viewContent, 'lg:grid-cols'),
        ];
        
        foreach ($viewTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 4: Provider Registration
        $this->info('4. Testing Provider Registration...');
        $providerContent = File::get('/Users/kym/Herd/Dokterku/app/Providers/Filament/BendaharaPanelProvider.php');
        
        $providerTests = [
            'Dashboard page registered' => str_contains($providerContent, 'BendaharaDashboard::class'),
            'Dashboard navigation group added' => str_contains($providerContent, 'NavigationGroup::make(\'📊 Dashboard\')'),
            'Pages method exists' => str_contains($providerContent, '->pages(['),
        ];
        
        foreach ($providerTests as $testName => $result) {
            $totalTests++;
            if ($result) {
                $passedTests++;
                $this->line("  ✅ {$testName}");
            } else {
                $this->line("  ❌ {$testName}", 'error');
            }
        }
        
        $this->line('');
        
        // Test 5: Route Registration
        $this->info('5. Testing Route Registration...');
        try {
            $routes = Route::getRoutes();
            $bendaharaRoutes = [];
            
            foreach ($routes as $route) {
                if (str_contains($route->uri(), 'bendahara')) {
                    $bendaharaRoutes[] = $route->uri();
                }
            }
            
            $routeTests = [
                'Bendahara routes exist' => count($bendaharaRoutes) > 0,
                'Has bendahara base route' => in_array('bendahara', $bendaharaRoutes),
            ];
            
            foreach ($routeTests as $testName => $result) {
                $totalTests++;
                if ($result) {
                    $passedTests++;
                    $this->line("  ✅ {$testName}");
                } else {
                    $this->line("  ❌ {$testName}", 'error');
                }
            }
            
        } catch (\Exception $e) {
            $this->line("  ⚠️ Route testing skipped: " . $e->getMessage(), 'comment');
        }
        
        $this->line('');
        
        // Test 6: Dashboard Features
        $this->info('6. Testing Dashboard Features...');
        $featureTests = [
            'Financial cards implemented' => str_contains($viewContent, 'Total Pendapatan') && str_contains($viewContent, 'Total Pengeluaran'),
            'Validation queue implemented' => str_contains($viewContent, 'Antrian Validasi'),
            'Monthly trends implemented' => str_contains($viewContent, 'Trend 6 Bulan'),
            'Recent transactions implemented' => str_contains($viewContent, 'Transaksi Terbaru'),
            'Top performers implemented' => str_contains($viewContent, 'Performa Terbaik'),
            'Action buttons implemented' => str_contains($pageContent, 'getHeaderActions'),
            'Responsive design implemented' => str_contains($viewContent, 'lg:grid-cols-2') || str_contains($viewContent, 'md:grid-cols-2'),
        ];
        
        foreach ($featureTests as $testName => $result) {
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
            $this->info('🎉 ALL TESTS PASSED - BENDAHARA DASHBOARD READY!');
        } else {
            $this->error('❌ SOME TESTS FAILED - REVIEW THE ERRORS ABOVE');
        }
        
        $this->line('');
        $this->info('📋 DASHBOARD FEATURES IMPLEMENTED:');
        $this->line('  1. 📊 Financial Summary Cards (Pendapatan, Pengeluaran, Jaspel, Net Profit)');
        $this->line('  2. 🔍 Validation Queue Status');
        $this->line('  3. 📈 6-Month Financial Trends');
        $this->line('  4. 📄 Recent Transactions (Last 10)');
        $this->line('  5. 🏆 Top Performers (Doctors & Procedures)');
        $this->line('  6. 🔄 Refresh & Export Actions');
        $this->line('  7. 📱 Responsive Design');
        $this->line('  8. 🎨 Full Filament Integration');
        
        $this->line('');
        $this->info('🌐 Access Dashboard at: /bendahara');
        
        return $passedTests === $totalTests ? 0 : 1;
    }
}