<?php

namespace App\Filament\Paramedis\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Helpers\AccurateTimeHelper;

class WelcomeWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Sync with other widgets
    
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }
    
    protected function getStats(): array
    {
        $user = auth()->user();
        $greeting = $this->getGreeting();
        
        return [
            Stat::make("👋 {$greeting}", $user->name)
                ->description('Selamat datang di Dashboard Paramedis')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('success'),
                
            Stat::make('📅 Hari Ini', AccurateTimeHelper::formatDate('d M Y'))
                ->description(AccurateTimeHelper::now()->format('l'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
                
            Stat::make('🏥 Klinik Dokterku', 'Online')
                ->description('Sistem berjalan normal')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
        ];
    }
    
    private function getGreeting(): string
    {
        $hour = AccurateTimeHelper::getHour();
        
        if ($hour < 11) {
            return 'Selamat Pagi';
        } elseif ($hour < 15) {
            return 'Selamat Siang';
        } elseif ($hour < 18) {
            return 'Selamat Sore';
        } else {
            return 'Selamat Malam';
        }
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
}