<?php

namespace App\Filament\Bendahara\Resources\ValidationQueueResource\Pages;

use App\Filament\Bendahara\Resources\ValidationQueueResource;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Services\TindakanValidationService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;

class ViewValidationQueue extends ViewRecord
{
    protected static string $resource = ValidationQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Assign to self action
            Actions\Action::make('assign_to_self')
                ->label('👤 Assign to Me')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (): bool => 
                    $this->record->status === 'pending' && 
                    !$this->record->assigned_to
                )
                ->action(function () {
                    $this->record->update([
                        'status' => 'assigned',
                        'assigned_to' => auth()->id(),
                    ]);
                    
                    Notification::make()
                        ->title('✅ Successfully Assigned')
                        ->body('Validation queue has been assigned to you')
                        ->success()
                        ->send();
                        
                    return redirect()->route('filament.bendahara.resources.validation-queues.view', ['record' => $this->record]);
                }),
                
            // Start review action
            Actions\Action::make('start_review')
                ->label('👁️ Start Review')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn (): bool => 
                    $this->record->status === 'assigned' && 
                    $this->record->assigned_to === auth()->id()
                )
                ->action(function () {
                    $this->record->update(['status' => 'in_review']);
                    
                    Notification::make()
                        ->title('👁️ Review Started')
                        ->body('Validation review has been started')
                        ->success()
                        ->send();
                        
                    return redirect()->route('filament.bendahara.resources.validation-queues.view', ['record' => $this->record]);
                }),
                
            // Approve action (Tindakan)
            Actions\Action::make('approve_tindakan')
                ->label('✅ Approve Tindakan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => 
                    $this->record->validation_type === 'tindakan' &&
                    in_array($this->record->status, ['assigned', 'in_review']) &&
                    $this->record->assigned_to === auth()->id()
                )
                ->requiresConfirmation()
                ->modalHeading('✅ Approve Tindakan')
                ->modalDescription('Are you sure you want to approve this tindakan? This action cannot be undone.')
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Approval Comment (Optional)')
                        ->placeholder('Add any comments about this approval...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $tindakan = Tindakan::find($this->record->validatable_id);
                    if ($tindakan) {
                        $validationService = new TindakanValidationService();
                        $result = $validationService->approve($tindakan, $data['approval_comment'] ?? null);
                        
                        Notification::make()
                            ->title('✅ Tindakan Approved')
                            ->body('Tindakan has been successfully approved')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.bendahara.resources.validation-queues.index');
                    }
                }),
                
            // Reject action (Tindakan)
            Actions\Action::make('reject_tindakan')
                ->label('❌ Reject Tindakan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => 
                    $this->record->validation_type === 'tindakan' &&
                    in_array($this->record->status, ['assigned', 'in_review']) &&
                    $this->record->assigned_to === auth()->id()
                )
                ->requiresConfirmation()
                ->modalHeading('❌ Reject Tindakan')
                ->modalDescription('Please provide a clear reason for rejection. This will be sent to the petugas.')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->placeholder('Please explain why this tindakan is being rejected...')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $tindakan = Tindakan::find($this->record->validatable_id);
                    if ($tindakan) {
                        $validationService = new TindakanValidationService();
                        $result = $validationService->reject($tindakan, $data['rejection_reason']);
                        
                        Notification::make()
                            ->title('❌ Tindakan Rejected')
                            ->body('Tindakan has been rejected and petugas has been notified')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.bendahara.resources.validation-queues.index');
                    }
                }),
                
            // Approve action (Pendapatan)
            Actions\Action::make('approve_pendapatan')
                ->label('✅ Approve Pendapatan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => 
                    $this->record->validation_type === 'pendapatan_harian' &&
                    in_array($this->record->status, ['assigned', 'in_review']) &&
                    $this->record->assigned_to === auth()->id()
                )
                ->requiresConfirmation()
                ->modalHeading('✅ Approve Pendapatan')
                ->modalDescription('Are you sure you want to approve this pendapatan? This action cannot be undone.')
                ->form([
                    Textarea::make('approval_comment')
                        ->label('Approval Comment (Optional)')
                        ->placeholder('Add any comments about this approval...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $pendapatan = PendapatanHarian::find($this->record->validatable_id);
                    if ($pendapatan) {
                        $pendapatan->update([
                            'status_validasi' => 'disetujui',
                            'validasi_by' => auth()->id(),
                            'validasi_at' => now(),
                            'catatan_validasi' => $data['approval_comment'] ?? 'Approved by bendahara',
                        ]);
                        
                        $this->record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('✅ Pendapatan Approved')
                            ->body('Pendapatan has been successfully approved')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.bendahara.resources.validation-queues.index');
                    }
                }),
                
            // Reject action (Pendapatan)
            Actions\Action::make('reject_pendapatan')
                ->label('❌ Reject Pendapatan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => 
                    $this->record->validation_type === 'pendapatan_harian' &&
                    in_array($this->record->status, ['assigned', 'in_review']) &&
                    $this->record->assigned_to === auth()->id()
                )
                ->requiresConfirmation()
                ->modalHeading('❌ Reject Pendapatan')
                ->modalDescription('Please provide a clear reason for rejection. This will be sent to the petugas.')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->placeholder('Please explain why this pendapatan is being rejected...')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $pendapatan = PendapatanHarian::find($this->record->validatable_id);
                    if ($pendapatan) {
                        $pendapatan->update([
                            'status_validasi' => 'ditolak',
                            'validasi_by' => auth()->id(),
                            'validasi_at' => now(),
                            'catatan_validasi' => $data['rejection_reason'],
                        ]);
                        
                        $this->record->update([
                            'status' => 'rejected',
                            'completed_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('❌ Pendapatan Rejected')
                            ->body('Pendapatan has been rejected and petugas has been notified')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.bendahara.resources.validation-queues.index');
                    }
                }),
        ];
    }

    public function getTitle(): string
    {
        $type = $this->record->validation_type === 'tindakan' ? '🏥 Tindakan' : '💰 Pendapatan';
        return "Validation Details - {$type}";
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        $statusLabel = match($this->record->status) {
            'pending' => '⏳ Pending Validation',
            'assigned' => '👤 Assigned to ' . ($this->record->assignedTo->name ?? 'Unknown'),
            'in_review' => '👁️ Under Review',
            'completed' => '✅ Completed',
            'rejected' => '❌ Rejected',
            default => ucfirst($this->record->status),
        };

        $priorityLabel = match($this->record->priority) {
            'urgent' => '🚨 Urgent Priority',
            'high' => '⚡ High Priority',
            'normal' => '📝 Normal Priority',
            'low' => '📋 Low Priority',
            default => '',
        };

        return "{$statusLabel} • {$priorityLabel}";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Add related record information
        if ($this->record->validation_type === 'tindakan') {
            $tindakan = Tindakan::with(['jenisTindakan', 'pasien', 'dokter'])->find($this->record->validatable_id);
            if ($tindakan) {
                $data['related_record_details'] = [
                    'jenis_tindakan' => $tindakan->jenisTindakan->nama ?? 'Unknown',
                    'pasien' => $tindakan->pasien->nama ?? 'Unknown',
                    'dokter' => $tindakan->dokter->nama ?? 'Unknown',
                    'tarif' => $tindakan->tarif,
                ];
            }
        } elseif ($this->record->validation_type === 'pendapatan_harian') {
            $pendapatan = PendapatanHarian::with(['pendapatan', 'user'])->find($this->record->validatable_id);
            if ($pendapatan) {
                $data['related_record_details'] = [
                    'jenis_pendapatan' => $pendapatan->pendapatan->nama_pendapatan ?? 'Unknown',
                    'user' => $pendapatan->user->name ?? 'Unknown',
                    'nominal' => $pendapatan->nominal,
                    'tanggal' => $pendapatan->tanggal_input,
                ];
            }
        }

        return $data;
    }
}