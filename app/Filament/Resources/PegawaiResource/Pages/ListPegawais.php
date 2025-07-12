<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Filament\Widgets\PegawaiStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Karyawan')
                ->icon('heroicon-m-plus')
                ->color('primary'),
        ];
    }

    public function getTitle(): string
    {
        return '🧑‍⚕️ Manajemen Pegawai';
    }

    public function getHeading(): string
    {
        return 'Daftar Karyawan';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PegawaiStatsWidget::class,
        ];
    }
}
