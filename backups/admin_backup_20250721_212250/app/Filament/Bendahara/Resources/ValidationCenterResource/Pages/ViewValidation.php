<?php

namespace App\Filament\Bendahara\Resources\ValidationCenterResource\Pages;

use App\Filament\Bendahara\Resources\ValidationCenterResource;
use App\Models\Tindakan;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Auth;

class ViewValidation extends ViewRecord
{
    protected static string $resource = ValidationCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->size(ActionSize::Small),
            
            // Quick Validation Actions
            Action::make('quick_approve')
                ->label('⚡ Quick Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->requiresConfirmation()
                ->modalHeading('⚡ Quick Approve')
                ->modalDescription('Approve this tindakan quickly without additional comments?')
                ->modalSubmitActionLabel('Approve Now')
                ->action(function () {
                    $this->quickValidate('approved');
                }),

            Action::make('approve_with_comment')
                ->label('✅ Approve with Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->form([
                    Forms\Components\Textarea::make('approval_comment')
                        ->label('Approval Comment')
                        ->placeholder('Add your validation notes...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->quickValidate('approved', $data['approval_comment'] ?? null);
                }),

            Action::make('reject_with_reason')
                ->label('❌ Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->placeholder('Please provide a clear reason for rejection...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->quickValidate('rejected', $data['rejection_reason']);
                }),

            // Review Actions for processed items
            Action::make('revert_to_pending')
                ->label('🔄 Revert to Pending')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status_validasi, ['approved', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('🔄 Revert to Pending Status')
                ->modalDescription('This will return the tindakan to pending status for re-validation.')
                ->modalSubmitActionLabel('Revert')
                ->form([
                    Forms\Components\Textarea::make('revert_reason')
                        ->label('Revert Reason')
                        ->required()
                        ->placeholder('Why is this being reverted for re-validation?')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->revertToPending($data['revert_reason']);
                }),

            // Additional Actions
            Action::make('add_note')
                ->label('📝 Add Note')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->form([
                    Forms\Components\Textarea::make('additional_note')
                        ->label('Additional Note')
                        ->required()
                        ->placeholder('Add a note to this validation...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->addValidationNote($data['additional_note']);
                }),

            Action::make('view_timeline')
                ->label('📅 View Timeline')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('📅 Validation Timeline')
                ->modalContent(fn () => view('filament.bendahara.validation-timeline', [
                    'record' => $this->record,
                    'timeline' => $this->getValidationTimeline()
                ]))
                ->modalWidth('3xl'),

            // Edit Action
            Actions\EditAction::make()
                ->label('✏️ Edit Details')
                ->visible(fn (): bool => Auth::user()->hasRole(['admin', 'bendahara']))
                ->modalWidth('4xl'),
        ];
    }

    public function getTitle(): string
    {
        return 'Validation Details';
    }

    public function getSubheading(): ?string
    {
        $status = match($this->record->status_validasi) {
            'pending' => '🕐 Pending Validation',
            'approved' => '✅ Approved',
            'rejected' => '❌ Rejected',
            default => ucfirst($this->record->status_validasi)
        };
        
        return "{$status} | {$this->record->jenisTindakan->nama} | Rp " . number_format($this->record->tarif);
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Could add related records widget, validation history, etc.
        ];
    }

    private function quickValidate(string $status, ?string $comment = null): void
    {
        try {
            $this->record->update([
                'status_validasi' => $status,
                'status' => $status === 'approved' ? 'selesai' : 'batal',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'komentar_validasi' => $comment ?? ($status === 'approved' ? 'Approved in detail view' : 'Rejected in detail view'),
            ]);

            $message = $status === 'approved' 
                ? '✅ Tindakan has been approved successfully'
                : '❌ Tindakan has been rejected';
            
            Notification::make()
                ->title('Validation Complete')
                ->body($message)
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Validation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function revertToPending(string $reason): void
    {
        try {
            $this->record->update([
                'status_validasi' => 'pending',
                'status' => 'pending',
                'validated_by' => null,
                'validated_at' => null,
                'komentar_validasi' => "🔄 Reverted by " . Auth::user()->name . ": {$reason}",
            ]);

            Notification::make()
                ->title('🔄 Successfully Reverted')
                ->body('Tindakan has been returned to pending status')
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Revert Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function addValidationNote(string $note): void
    {
        try {
            $currentComment = $this->record->komentar_validasi ?? '';
            $timestamp = now()->format('d/m/Y H:i');
            $newNote = "📝 Note by " . Auth::user()->name . " ({$timestamp}): {$note}";
            
            $this->record->update([
                'komentar_validasi' => $currentComment ? "{$currentComment}\n{$newNote}" : $newNote,
            ]);

            Notification::make()
                ->title('📝 Note Added')
                ->body('Validation note has been added successfully')
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Failed to Add Note')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getValidationTimeline(): array
    {
        $timeline = [];
        
        // Created
        $timeline[] = [
            'event' => 'Created',
            'timestamp' => $this->record->created_at,
            'user' => $this->record->inputBy->name ?? 'System',
            'description' => 'Tindakan was created and submitted',
            'icon' => 'heroicon-o-plus-circle',
            'color' => 'info'
        ];
        
        // Validation events
        if ($this->record->validated_at) {
            $timeline[] = [
                'event' => match($this->record->status_validasi) {
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    default => 'Validated'
                },
                'timestamp' => $this->record->validated_at,
                'user' => $this->record->validatedBy->name ?? 'Unknown',
                'description' => $this->record->komentar_validasi ?? 'No comment provided',
                'icon' => match($this->record->status_validasi) {
                    'approved' => 'heroicon-o-check-circle',
                    'rejected' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-clock'
                },
                'color' => match($this->record->status_validasi) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'warning'
                }
            ];
        }
        
        // Sort by timestamp
        usort($timeline, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return $timeline;
    }
}