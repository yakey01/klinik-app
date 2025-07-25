<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTindakans extends ListRecords
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Tindakan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}