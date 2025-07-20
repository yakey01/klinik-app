<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Page;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;

class JaspelPremiumPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static string $view = 'paramedis.jaspel';
    protected static ?string $slug = 'jaspel';
    
    protected static ?string $title = 'Jaspel - Service Fee Management';
    
    protected static ?string $navigationLabel = 'Jaspel';
    
    protected static ?int $navigationSort = 2;
    
    protected static bool $shouldRegisterNavigation = true;
    
    // Security: Only accessible by paramedis role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    public function getViewData(): array
    {
        $user = Auth::user();
        
        // Sample Jaspel data for demonstration (replace with real data when Jaspel model is available)
        $jaspelStats = [
            'monthlyJaspel' => 8720000,
            'weeklyJaspel' => 2180000,
            'dailyJaspel' => 435000,
            'totalYearlyJaspel' => 96250000,
            'averagePerShift' => 145000,
            'completionRate' => 94,
        ];
        
        // Chart data for Jaspel trends
        $chartData = [
            'monthlyTrend' => [
                'labels' => collect(range(11, 0))->map(fn($months) => now()->subMonths($months)->format('M Y'))->toArray(),
                'data' => collect(range(1, 12))->map(fn() => rand(6000000, 12000000))->toArray(),
            ],
            'categoryDistribution' => [
                'labels' => ['Konsultasi', 'Tindakan Medis', 'Operasi', 'Emergency', 'Rawat Inap'],
                'data' => [35, 28, 15, 12, 10],
            ],
        ];
        
        // Sample Jaspel history
        $jaspelHistory = collect([
            [
                'date' => '2024-01-15',
                'patient' => 'Ahmad Suharto',
                'procedure' => 'Konsultasi Umum',
                'amount' => 150000,
                'status' => 'validated',
                'doctor' => 'Dr. Sarah Wijaya',
                'time' => '09:30'
            ],
            [
                'date' => '2024-01-14',
                'patient' => 'Siti Nurhaliza',
                'procedure' => 'Pemeriksaan Laboratorium',
                'amount' => 280000,
                'status' => 'pending',
                'doctor' => 'Dr. Michael Chen',
                'time' => '14:15'
            ],
            [
                'date' => '2024-01-14',
                'patient' => 'Budi Santoso',
                'procedure' => 'Tindakan Emergency',
                'amount' => 500000,
                'status' => 'validated',
                'doctor' => 'Dr. Sarah Wijaya',
                'time' => '20:45'
            ],
            [
                'date' => '2024-01-13',
                'patient' => 'Maya Sari',
                'procedure' => 'Operasi Minor',
                'amount' => 750000,
                'status' => 'validated',
                'doctor' => 'Dr. Robert Kim',
                'time' => '11:00'
            ],
            [
                'date' => '2024-01-12',
                'patient' => 'Joko Widodo',
                'procedure' => 'Rawat Inap',
                'amount' => 320000,
                'status' => 'rejected',
                'doctor' => 'Dr. Michael Chen',
                'time' => '16:30'
            ],
        ]);
        
        return [
            'user' => $user,
            'jaspelStats' => $jaspelStats,
            'chartData' => $chartData,
            'jaspelHistory' => $jaspelHistory,
        ];
    }
}