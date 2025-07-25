<?php

namespace App\Filament\Bendahara\Resources\JaspelManagementResource\Pages;

use App\Filament\Bendahara\Resources\JaspelManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJaspelManagement extends ListRecords
{
    protected static string $resource = JaspelManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Jaspel Manual')
                ->icon('heroicon-o-plus'),
        ];
    }
}