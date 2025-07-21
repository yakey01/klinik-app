<?php

namespace App\Filament\Resources\SecurityLogResource\Pages;

use App\Filament\Resources\SecurityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecurityLogs extends ListRecords
{
    protected static string $resource = SecurityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->refreshTable()),
        ];
    }
}