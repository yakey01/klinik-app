<?php

namespace App\Filament\Dokter\Resources\JaspelResource\Pages;

use App\Filament\Dokter\Resources\JaspelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Dokter;
use App\Models\Tindakan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ListJaspels extends ListRecords
{
    protected static string $resource = JaspelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_jaspel')
                ->label('Export Jaspel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $this->notify('success', 'Export jaspel akan dimulai dalam beberapa saat');
                }),
                
            Actions\Action::make('monthly_report')
                ->label('Laporan Bulanan')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->action(function () {
                    $user = Auth::user();
                    $dokter = Dokter::where('user_id', $user->id)->first();
                    
                    if (!$dokter) {
                        $this->notify('danger', 'Data dokter tidak ditemukan');
                        return;
                    }
                    
                    // Generate monthly report
                    $this->notify('info', 'Laporan bulanan sedang diproses...');
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            JaspelResource\Widgets\JaspelOverviewWidget::class,
        ];
    }
}