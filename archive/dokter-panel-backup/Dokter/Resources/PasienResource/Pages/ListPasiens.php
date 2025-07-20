<?php

namespace App\Filament\Dokter\Resources\PasienResource\Pages;

use App\Filament\Dokter\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPasiens extends ListRecords
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_patients')
                ->label('Export Data Pasien')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $this->notify('success', 'Export data pasien akan dimulai dalam beberapa saat');
                }),
        ];
    }
}