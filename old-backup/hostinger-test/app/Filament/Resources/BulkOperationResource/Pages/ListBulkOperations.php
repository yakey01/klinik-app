<?php

namespace App\Filament\Resources\BulkOperationResource\Pages;

use App\Filament\Resources\BulkOperationResource;
use App\Models\BulkOperation;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBulkOperations extends ListRecords
{
    protected static string $resource = BulkOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clean up old operations')
                ->modalDescription('This will delete completed, failed, and cancelled operations older than 30 days.')
                ->action(function () {
                    $deleted = BulkOperation::cleanup(30);
                    \Filament\Notifications\Notification::make()
                        ->title("Cleaned up {$deleted} old operations")
                        ->success()
                        ->send();
                }),
        ];
    }
}