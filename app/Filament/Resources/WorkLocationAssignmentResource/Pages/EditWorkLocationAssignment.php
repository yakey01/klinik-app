<?php

namespace App\Filament\Resources\WorkLocationAssignmentResource\Pages;

use App\Filament\Resources\WorkLocationAssignmentResource;
use App\Models\AssignmentHistory;
use App\Services\SmartWorkLocationAssignmentService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWorkLocationAssignment extends EditRecord
{
    protected static string $resource = WorkLocationAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('get_smart_recommendation')
                ->label('🧠 Get Smart Recommendation')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->action(function () {
                    $service = app(SmartWorkLocationAssignmentService::class);
                    $recommendations = $service->getAssignmentRecommendations($this->record);
                    
                    $topRecommendation = $recommendations['top_recommendation'];
                    
                    if ($topRecommendation && $topRecommendation['score'] > 40) {
                        $this->form->fill([
                            'work_location_id' => $topRecommendation['location']->id
                        ]);
                        
                        Notification::make()
                            ->title('🎯 Smart Recommendation Applied')
                            ->body("Recommended: {$topRecommendation['location']->name} with {$topRecommendation['confidence']} confidence (Score: {$topRecommendation['score']})")
                            ->success()
                            ->duration(5000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('⚠️ No Strong Recommendation')
                            ->body('No location found with high confidence. Current assignment may be optimal.')
                            ->warning()
                            ->send();
                    }
                }),

            Actions\Action::make('view_assignment_history')
                ->label('📚 View History')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading(fn () => "📚 Assignment History for {$this->record->name}")
                ->modalContent(function () {
                    $histories = AssignmentHistory::where('user_id', $this->record->id)
                        ->with(['workLocation', 'assignedBy'])
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                    
                    if ($histories->isEmpty()) {
                        return new \Illuminate\Support\HtmlString('<p class="text-gray-500">No assignment history found.</p>');
                    }
                    
                    $content = '<div class="space-y-4">';
                    
                    foreach ($histories as $history) {
                        $content .= '<div class="border rounded-lg p-4 bg-gray-50">';
                        $content .= '<div class="flex justify-between items-start mb-2">';
                        $content .= '<h4 class="font-bold">' . $history->workLocation->name . '</h4>';
                        $content .= '<span class="text-sm text-gray-500">' . $history->created_at->diffForHumans() . '</span>';
                        $content .= '</div>';
                        
                        $content .= '<div class="text-sm space-y-1">';
                        $content .= '<p><strong>Method:</strong> ' . $history->method_label . '</p>';
                        $content .= '<p><strong>Assigned by:</strong> ' . ($history->assignedBy->name ?? 'System') . '</p>';
                        
                        if ($history->assignment_score) {
                            $content .= '<p><strong>Score:</strong> ' . $history->assignment_score . '/100</p>';
                        }
                        
                        if ($history->confidence_badge) {
                            $content .= '<p><strong>Confidence:</strong> ' . $history->confidence_badge . '</p>';
                        }
                        
                        if ($history->formatted_reasons) {
                            $content .= '<p><strong>Reasons:</strong> ' . $history->formatted_reasons . '</p>';
                        }
                        
                        if ($history->notes) {
                            $content .= '<p><strong>Notes:</strong> ' . $history->notes . '</p>';
                        }
                        
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    
                    $content .= '</div>';
                    
                    return new \Illuminate\Support\HtmlString($content);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Actions\DeleteAction::make()
                ->label('🗑️ Remove Assignment')
                ->modalHeading('Remove Work Location Assignment')
                ->modalDescription('This will remove the work location assignment but keep the user account.')
                ->action(function () {
                    $previousLocation = $this->record->workLocation;
                    
                    // Create history record before removing
                    if ($previousLocation) {
                        AssignmentHistory::create([
                            'user_id' => $this->record->id,
                            'work_location_id' => $previousLocation->id,
                            'previous_work_location_id' => $previousLocation->id,
                            'assigned_by' => auth()->id(),
                            'assignment_method' => 'manual',
                            'assignment_reasons' => ['Assignment removed via edit page'],
                            'metadata' => [
                                'action' => 'removed',
                                'removed_by' => auth()->user()->name,
                                'timestamp' => now()->toISOString()
                            ]
                        ]);
                    }
                    
                    $this->record->work_location_id = null;
                    $this->record->save();
                    
                    Notification::make()
                        ->title('🗑️ Assignment Removed')
                        ->body("Work location assignment removed for {$this->record->name}")
                        ->warning()
                        ->send();
                        
                    return redirect()->route('filament.admin.resources.work-location-assignments.index');
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Check if work_location_id was changed
        if ($this->record->wasChanged('work_location_id')) {
            $previousLocationId = $this->record->getOriginal('work_location_id');
            
            // Create assignment history
            AssignmentHistory::create([
                'user_id' => $this->record->id,
                'work_location_id' => $this->record->work_location_id,
                'previous_work_location_id' => $previousLocationId,
                'assigned_by' => auth()->id(),
                'assignment_method' => 'manual',
                'assignment_reasons' => ['Assignment updated via admin panel'],
                'metadata' => [
                    'updated_by_name' => auth()->user()->name,
                    'timestamp' => now()->toISOString(),
                    'notes' => $this->data['assignment_notes'] ?? null
                ],
                'notes' => $this->data['assignment_notes'] ?? null
            ]);

            $locationName = $this->record->workLocation->name ?? 'None';
            
            Notification::make()
                ->title('✅ Assignment Updated Successfully')
                ->body("Updated assignment for {$this->record->name} to {$locationName}")
                ->success()
                ->send();
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null; // We handle this in afterSave
    }
}