<?php

namespace App\Filament\Resources\WorkLocationAssignmentResource\Pages;

use App\Filament\Resources\WorkLocationAssignmentResource;
use App\Models\AssignmentHistory;
use App\Services\SmartWorkLocationAssignmentService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateWorkLocationAssignment extends CreateRecord
{
    protected static string $resource = WorkLocationAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('get_smart_recommendation')
                ->label('🧠 Get Smart Recommendation')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->visible(fn () => $this->record?->id)
                ->action(function () {
                    if (!$this->record) return;
                    
                    $service = app(SmartWorkLocationAssignmentService::class);
                    $recommendations = $service->getAssignmentRecommendations($this->record);
                    
                    $topRecommendation = $recommendations['top_recommendation'];
                    
                    if ($topRecommendation && $topRecommendation['score'] > 40) {
                        $this->form->fill([
                            'work_location_id' => $topRecommendation['location']->id
                        ]);
                        
                        Notification::make()
                            ->title('🎯 Smart Recommendation Applied')
                            ->body("Recommended: {$topRecommendation['location']->name} with {$topRecommendation['confidence']} confidence")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('⚠️ No Strong Recommendation')
                            ->body('No location found with high confidence. Manual selection recommended.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }

    protected function afterCreate(): void
    {
        // Create assignment history
        AssignmentHistory::create([
            'user_id' => $this->record->id,
            'work_location_id' => $this->record->work_location_id,
            'previous_work_location_id' => $this->record->getOriginal('work_location_id'),
            'assigned_by' => auth()->id(),
            'assignment_method' => 'manual',
            'assignment_reasons' => ['Manual assignment via admin panel'],
            'metadata' => [
                'assigned_by_name' => auth()->user()->name,
                'timestamp' => now()->toISOString(),
                'notes' => $this->data['assignment_notes'] ?? null
            ],
            'notes' => $this->data['assignment_notes'] ?? null
        ]);

        Notification::make()
            ->title('✅ Assignment Created Successfully')
            ->body("Assigned {$this->record->name} to {$this->record->workLocation->name}")
            ->success()
            ->send();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // We handle this in afterCreate
    }
}