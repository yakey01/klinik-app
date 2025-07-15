<?php

namespace App\Filament\Resources\BulkOperationResource\Pages;

use App\Filament\Resources\BulkOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBulkOperation extends ViewRecord
{
    protected static string $resource = BulkOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canCancel())
                ->requiresConfirmation()
                ->action(function () {
                    $bulkService = app(\App\Services\BulkOperationService::class);
                    try {
                        $bulkService->cancelOperation($this->record);
                        \Filament\Notifications\Notification::make()
                            ->title('Operation cancelled successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Failed to cancel operation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}