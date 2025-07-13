<?php

namespace App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListValidasiJumlahPasiens extends ListRecords
{
    protected static string $resource = ValidasiJumlahPasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->header('Referer'))),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Data')
                ->badge(fn () => \App\Models\JumlahPasienHarian::count()),
                
            'pending' => Tab::make('Menunggu Validasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending'))
                ->badge(fn () => \App\Models\JumlahPasienHarian::where('status_validasi', 'pending')->count())
                ->badgeColor('warning'),
                
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'approved'))
                ->badge(fn () => \App\Models\JumlahPasienHarian::where('status_validasi', 'approved')->count())
                ->badgeColor('success'),
                
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'rejected'))
                ->badge(fn () => \App\Models\JumlahPasienHarian::where('status_validasi', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}