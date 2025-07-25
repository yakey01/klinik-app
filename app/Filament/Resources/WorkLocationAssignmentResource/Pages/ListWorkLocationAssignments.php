<?php

namespace App\Filament\Resources\WorkLocationAssignmentResource\Pages;

use App\Filament\Resources\WorkLocationAssignmentResource;
use App\Models\User;
use App\Services\SmartWorkLocationAssignmentService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListWorkLocationAssignments extends ListRecords
{
    protected static string $resource = WorkLocationAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('smart_assign_all')
                ->label('🚀 Smart Assign All Unassigned')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('🧠 Bulk Smart Assignment')
                ->modalDescription('This will use AI-powered algorithms to automatically assign all unassigned users to the most suitable work locations based on their profiles.')
                ->modalSubmitActionLabel('Execute Smart Assignment')
                ->action(function () {
                    $unassignedUsers = User::whereNull('work_location_id')->get();
                    
                    if ($unassignedUsers->isEmpty()) {
                        Notification::make()
                            ->title('ℹ️ No Action Needed')
                            ->body('All users already have work locations assigned')
                            ->info()
                            ->send();
                        return;
                    }
                    
                    $service = app(SmartWorkLocationAssignmentService::class);
                    $result = $service->bulkIntelligentAssignment($unassignedUsers);
                    
                    Notification::make()
                        ->title('🎯 Bulk Smart Assignment Completed!')
                        ->body("Successfully assigned {$result['successful']} users. {$result['failed']} failed assignments.")
                        ->success()
                        ->duration(8000)
                        ->send();
                }),

            Actions\Action::make('view_analytics')
                ->label('📊 View Analytics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('📊 Assignment Analytics Dashboard')
                ->modalContent(function () {
                    $service = app(SmartWorkLocationAssignmentService::class);
                    $analytics = $service->getAssignmentAnalytics();
                    
                    $content = '<div class="space-y-6">';
                    
                    // Overview Section
                    $content .= '<div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border">';
                    $content .= '<h3 class="font-bold text-lg mb-3">🎯 Assignment Overview</h3>';
                    $content .= '<div class="grid grid-cols-2 gap-4">';
                    $content .= '<div class="text-center">';
                    $content .= '<div class="text-2xl font-bold text-green-600">' . $analytics['overview']['users_with_location'] . '</div>';
                    $content .= '<div class="text-sm text-gray-600">Users Assigned</div>';
                    $content .= '</div>';
                    $content .= '<div class="text-center">';
                    $content .= '<div class="text-2xl font-bold text-red-600">' . $analytics['overview']['users_without_location'] . '</div>';
                    $content .= '<div class="text-sm text-gray-600">Needs Assignment</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mt-3 text-center">';
                    $content .= '<div class="text-lg font-semibold">Coverage: ' . $analytics['overview']['assignment_coverage'] . '%</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    
                    // Location Distribution
                    $content .= '<div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border">';
                    $content .= '<h3 class="font-bold text-lg mb-3">📍 Location Distribution</h3>';
                    foreach ($analytics['location_distribution'] as $location) {
                        $color = match(true) {
                            $location['utilization_percentage'] >= 90 => 'text-red-600',
                            $location['utilization_percentage'] >= 75 => 'text-yellow-600',
                            $location['utilization_percentage'] >= 50 => 'text-green-600',
                            default => 'text-blue-600'
                        };
                        
                        $content .= '<div class="flex justify-between items-center py-2 border-b last:border-b-0">';
                        $content .= '<span class="font-medium">' . $location['location'] . '</span>';
                        $content .= '<span class="' . $color . '">' . $location['users_count'] . ' users (' . $location['utilization_percentage'] . '%)</span>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    
                    $content .= '</div>';
                    
                    return new \Illuminate\Support\HtmlString($content);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Actions\CreateAction::make()
                ->label('➕ Manual Assignment')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Custom widgets can be added here for real-time stats
        ];
    }
}