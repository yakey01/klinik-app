<?php

namespace App\Filament\Dokter\Resources\JaspelDokterResource\Pages;

use App\Filament\Dokter\Resources\JaspelDokterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJaspelDokters extends ListRecords
{
    protected static string $resource = JaspelDokterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are handled by the resource
        ];
    }

    public function getTitle(): string
    {
        return 'Data Jaspel Saya';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $this->getModel()::query()->count()),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending'))
                ->badge(fn () => $this->getModel()::query()->where('status_validasi', 'pending')->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui'))
                ->badge(fn () => $this->getModel()::query()->where('status_validasi', 'disetujui')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'ditolak'))
                ->badge(fn () => $this->getModel()::query()->where('status_validasi', 'ditolak')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed for jaspel summary
        ];
    }
}