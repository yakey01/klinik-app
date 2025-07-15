<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup')
                ->label('🧹 Cleanup Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cleanup Old Logs')
                ->modalDescription('This will permanently delete old log entries. Are you sure?')
                ->action(function () {
                    $loggingService = new \App\Services\LoggingService();
                    $deleted = $loggingService->cleanupOldLogs(30);
                    
                    $this->notify(
                        'success',
                        'Logs cleaned up successfully',
                        "Deleted {$deleted['activity']} activity logs"
                    );
                }),
        ];
    }
}