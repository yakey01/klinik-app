<?php

namespace App\Filament\Dokter\Pages;

use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DokterDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $routePath = '/';
    protected static ?string $title = 'Doctor Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Dokter\Widgets\TodayOverviewWidget::class,
            \App\Filament\Dokter\Widgets\MonthlyJaspelWidget::class,
            \App\Filament\Dokter\Widgets\ScheduleWidget::class,
            \App\Filament\Dokter\Widgets\PerformanceMetricsWidget::class,
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Dokter\Widgets\WelcomeWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('mobile-app')
                ->label('Mobile App')
                ->icon('heroicon-o-device-phone-mobile')
                ->url(fn (): string => route('dokter.mobile-app'))
                ->openUrlInNewTab()
                ->color('primary'),
                
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // Clear relevant caches
                    cache()->forget("dokter_dashboard_stats_" . auth()->id());
                    $this->redirect(request()->header('Referer'));
                })
                ->color('gray'),
        ];
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return [
                'error' => 'Data dokter tidak ditemukan',
                'dokter' => null,
                'stats' => []
            ];
        }

        // Get today's statistics
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        $stats = [
            'patients_today' => Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->distinct('pasien_id')
                ->count(),
            
            'procedures_today' => Tindakan::where('dokter_id', $dokter->id)
                ->whereDate('tanggal_tindakan', $today)
                ->count(),
            
            'jaspel_month' => Tindakan::where('dokter_id', $dokter->id)
                ->where('tanggal_tindakan', '>=', $thisMonth)
                ->where('status_validasi', 'disetujui')
                ->sum('jasa_dokter'),
            
            'next_schedule' => JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', $today)
                ->orderBy('tanggal_jaga')
                ->first(),
        ];

        return [
            'dokter' => $dokter,
            'stats' => $stats,
        ];
    }
}