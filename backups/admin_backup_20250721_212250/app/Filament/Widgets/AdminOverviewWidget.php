<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Role;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;

class AdminOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    // protected static ?string $pollingInterval = null; // DISABLED - was causing refresh loops
    
    protected function getStats(): array
    {
        // Get the same stats as the legacy AdminDashboardController
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $totalPatients = Pasien::count();
        $totalProcedures = Tindakan::count();
        $totalIncome = Pendapatan::sum('jumlah');
        $totalExpenses = Pengeluaran::sum('jumlah');
        $pendingApprovals = Pendapatan::where('status', 'pending')->count() + 
                           Pengeluaran::where('status', 'pending')->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('System users')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
                
            Stat::make('Active Roles', $totalRoles)
                ->description('User roles')
                ->descriptionIcon('heroicon-o-identification')
                ->color('primary'),
                
            Stat::make('Patients', $totalPatients)
                ->description('Registered patients')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info'),
                
            Stat::make('Procedures', $totalProcedures)
                ->description('Medical procedures')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('warning'),
                
            Stat::make('Total Income', 'Rp ' . number_format($totalIncome, 0, ',', '.'))
                ->description('Total revenue')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
                
            Stat::make('Total Expenses', 'Rp ' . number_format($totalExpenses, 0, ',', '.'))
                ->description('Total expenses')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('danger'),
                
            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
}