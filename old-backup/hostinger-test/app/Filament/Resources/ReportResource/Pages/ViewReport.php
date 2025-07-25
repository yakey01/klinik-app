<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Services\ReportService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('execute')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function () {
                    $reportService = app(ReportService::class);
                    try {
                        $execution = $reportService->executeReport($this->record, Auth::user());
                        Notification::make()
                            ->title('Report executed successfully')
                            ->body("Execution completed in {$execution->getFormattedExecutionTime()}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Report execution failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}