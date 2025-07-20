<?php

namespace App\Filament\Dokter\Resources\JadwalJagaResource\Pages;

use App\Filament\Dokter\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\JadwalJaga;
use Illuminate\Support\Facades\Auth;

class ListJadwalJagas extends ListRecords
{
    protected static string $resource = JadwalJagaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar_view')
                ->label('Lihat Kalender')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->action(function () {
                    $this->notify('info', 'Fitur kalender akan segera hadir');
                }),
                
            Actions\Action::make('export_schedule')
                ->label('Export Jadwal')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $this->notify('success', 'Export jadwal akan dimulai dalam beberapa saat');
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            JadwalJagaResource\Widgets\ScheduleOverviewWidget::class,
        ];
    }
}