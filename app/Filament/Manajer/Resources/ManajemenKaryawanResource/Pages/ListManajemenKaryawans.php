<?php

namespace App\Filament\Manajer\Resources\ManajemenKaryawanResource\Pages;

use App\Filament\Manajer\Resources\ManajemenKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListManajemenKaryawans extends ListRecords
{
    protected static string $resource = ManajemenKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('🔄 Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->refreshPage()),
                
            Actions\Action::make('export_all')
                ->label('📊 Export Staff Report')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('📊 Staff Report Export')
                        ->body('Employee performance report is being generated...')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('👥 Semua Karyawan')
                ->badge(\App\Models\User::whereHas('role', function (Builder $query) {
                    $query->whereIn('name', ['dokter', 'paramedis', 'perawat', 'bendahara', 'petugas', 'non_paramedis']);
                })->count()),
                
            'medical_staff' => Tab::make('👨‍⚕️ Tim Medis')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', function (Builder $q) {
                    $q->whereIn('name', ['dokter', 'paramedis', 'perawat']);
                }))
                ->badge(\App\Models\User::whereHas('role', function (Builder $query) {
                    $query->whereIn('name', ['dokter', 'paramedis', 'perawat']);
                })->count()),
                
            'support_staff' => Tab::make('📋 Tim Support')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', function (Builder $q) {
                    $q->whereIn('name', ['bendahara', 'petugas', 'non_paramedis']);
                }))
                ->badge(\App\Models\User::whereHas('role', function (Builder $query) {
                    $query->whereIn('name', ['bendahara', 'petugas', 'non_paramedis']);
                })->count()),
                
            'active' => Tab::make('✅ Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(\App\Models\User::where('is_active', true)->whereHas('role', function (Builder $query) {
                    $query->whereIn('name', ['dokter', 'paramedis', 'perawat', 'bendahara', 'petugas', 'non_paramedis']);
                })->count()),
        ];
    }
}