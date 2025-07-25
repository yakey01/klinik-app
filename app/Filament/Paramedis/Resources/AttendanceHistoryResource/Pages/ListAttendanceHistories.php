<?php

namespace App\Filament\Paramedis\Resources\AttendanceHistoryResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListAttendanceHistories extends ListRecords
{
    protected static string $resource = AttendanceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_report')
                ->label('Export Laporan')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function (array $data) {
                    // Will implement export functionality later
                    $this->notify('success', 'Fitur export akan segera tersedia!');
                })
                ->visible(fn () => $this->getTable()->getRecords()->count() > 0),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge($this->getTabBadgeCount())
                ->badgeColor('primary'),
                
            'this_month' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereYear('date', Carbon::now()->year)
                    ->whereMonth('date', Carbon::now()->month)
                )
                ->badge($this->getTabBadgeCount('this_month'))
                ->badgeColor('success'),
                
            'last_month' => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereYear('date', Carbon::now()->subMonth()->year)
                    ->whereMonth('date', Carbon::now()->subMonth()->month)
                )
                ->badge($this->getTabBadgeCount('last_month'))
                ->badgeColor('warning'),
                
            'this_week' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereBetween('date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                )
                ->badge($this->getTabBadgeCount('this_week'))
                ->badgeColor('info'),
                
            'incomplete' => Tab::make('Belum Check Out')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('time_in')
                    ->whereNull('time_out')
                )
                ->badge($this->getTabBadgeCount('incomplete'))
                ->badgeColor('danger'),
        ];
    }

    protected function getTabBadgeCount(string $tab = 'all'): int
    {
        $query = $this->getResource()::getEloquentQuery();
        
        return match ($tab) {
            'this_month' => $query
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->count(),
                
            'last_month' => $query
                ->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month)
                ->count(),
                
            'this_week' => $query
                ->whereBetween('date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count(),
                
            'incomplete' => $query
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->count(),
                
            default => $query->count(),
        };
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [25, 50, 100];
    }

    public function getTitle(): string
    {
        return 'Riwayat Presensi';
    }

    public function getHeading(): string
    {
        return 'Riwayat Presensi Saya';
    }

    public function getSubheading(): string
    {
        $user = auth()->user();
        $currentMonthCount = $this->getTabBadgeCount('this_month');
        $incompleteCount = $this->getTabBadgeCount('incomplete');
        
        $subheading = "Data presensi untuk {$user->name}";
        
        if ($currentMonthCount > 0) {
            $subheading .= " • {$currentMonthCount} hari bulan ini";
        }
        
        if ($incompleteCount > 0) {
            $subheading .= " • {$incompleteCount} belum check out";
        }
        
        return $subheading;
    }
}