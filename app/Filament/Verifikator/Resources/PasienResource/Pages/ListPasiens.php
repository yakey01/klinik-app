<?php

namespace App\Filament\Verifikator\Resources\PasienResource\Pages;

use App\Filament\Verifikator\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPasiens extends ListRecords
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => \App\Models\Pasien::count()),
            
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Pasien::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'verified' => Tab::make('Terverifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'verified'))
                ->badge(fn () => \App\Models\Pasien::where('status', 'verified')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => \App\Models\Pasien::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}