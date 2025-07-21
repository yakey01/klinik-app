<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PermohonanCuti;
use App\Models\Pegawai;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ApprovalQueueWidget extends BaseWidget
{
    protected static ?string $heading = '⚙️ Approval Queue Management';
    
    protected static ?int $sort = 5;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanCuti::query()
                    ->with(['pegawai'])
                    ->where('status', 'Menunggu')
                    ->orderBy('created_at', 'asc')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->prefix('#')
                    ->sortable()
                    ->size('sm')
                    ->color('gray'),
                    
                Tables\Columns\ImageColumn::make('pegawai.avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->pegawai->name ?? 'N/A') . '&background=f59e0b&color=fff')
                    ->size(35),
                    
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Employee')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->pegawai->jabatan ?? 'No position')
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Leave Type')
                    ->badge()
                    ->color(fn($value) => match($value) {
                        'tahunan' => 'success',
                        'sakit' => 'danger',
                        'melahirkan' => 'info',
                        'darurat' => 'warning',
                        default => 'gray'
                    })
                    ->icon(fn($value) => match($value) {
                        'tahunan' => 'heroicon-m-calendar-days',
                        'sakit' => 'heroicon-m-heart',
                        'melahirkan' => 'heroicon-m-baby',
                        'darurat' => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-document'
                    }),
                    
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn($record) => 
                        $record->tanggal_mulai < now() ? 'danger' : 'success'
                    )
                    ->description(fn($record) => $record->tanggal_mulai->diffForHumans()),
                    
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn($record) => $record->tanggal_selesai->diffForHumans()),
                    
                Tables\Columns\TextColumn::make('durasi_hari')
                    ->label('Duration')
                    ->alignCenter()
                    ->suffix(' days')
                    ->sortable()
                    ->badge()
                    ->color(fn($value) => match(true) {
                        $value <= 3 => 'success',
                        $value <= 7 => 'warning',
                        default => 'danger'
                    }),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $daysToStart = $record->tanggal_mulai->diffInDays(now(), false);
                        return match(true) {
                            $daysToStart > 0 => 'Overdue',
                            $daysToStart >= -1 => 'Urgent',
                            $daysToStart >= -3 => 'High',
                            $daysToStart >= -7 => 'Medium',
                            default => 'Low'
                        };
                    })
                    ->color(fn($state) => match($state) {
                        'Overdue' => 'danger',
                        'Urgent' => 'warning',
                        'High' => 'info',
                        'Medium' => 'success',
                        'Low' => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable()
                    ->description(fn($record) => $record->created_at->format('M j, Y H:i'))
                    ->color('gray')
                    ->size('sm'),
                    
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->keterangan)
                    ->wrap()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_cuti')
                    ->label('Leave Type')
                    ->options([
                        'tahunan' => 'Annual Leave',
                        'sakit' => 'Sick Leave',
                        'melahirkan' => 'Maternity Leave',
                        'darurat' => 'Emergency Leave',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('pegawai_id')
                    ->label('Employee')
                    ->relationship('pegawai', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('urgent_requests')
                    ->label('Urgent Requests')
                    ->query(fn(Builder $query) => 
                        $query->whereDate('tanggal_mulai', '<=', now()->addDays(3))
                    )
                    ->toggle(),
                    
                Tables\Filters\Filter::make('long_duration')
                    ->label('Long Duration (>7 days)')
                    ->query(fn(Builder $query) => $query->where('durasi_hari', '>', 7))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Leave Request')
                    ->modalDescription('Are you sure you want to approve this leave request?')
                    ->modalSubmitActionLabel('Approve')
                    ->form([
                        Textarea::make('approval_note')
                            ->label('Approval Note (Optional)')
                            ->placeholder('Add any notes for this approval...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'Disetujui',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_note' => $data['approval_note'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title('Leave Request Approved')
                            ->body('Leave request for ' . $record->pegawai->name . ' has been approved.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->status === 'Menunggu'),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Leave Request')
                    ->modalDescription('Are you sure you want to reject this leave request?')
                    ->modalSubmitActionLabel('Reject')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Please provide a reason for rejection...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'Ditolak',
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        
                        Notification::make()
                            ->title('Leave Request Rejected')
                            ->body('Leave request for ' . $record->pegawai->name . ' has been rejected.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn($record) => $record->status === 'Menunggu'),
                    
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Leave Request Details - ' . $record->pegawai->name)
                    ->modalContent(fn($record) => view('filament.manajer.modals.leave-details', compact('record')))
                    ->modalActions([
                        Tables\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray')
                            ->close(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Multiple Leave Requests')
                    ->modalDescription('Are you sure you want to approve all selected leave requests?')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status' => 'Disetujui',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ]);
                        });
                        
                        Notification::make()
                            ->title('Bulk Approval Completed')
                            ->body($records->count() . ' leave requests have been approved.')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\BulkAction::make('export_queue')
                    ->label('Export Queue')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('info')
                    ->action(function ($records) {
                        Notification::make()
                            ->title('Export Initiated')
                            ->body('Approval queue data is being exported.')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('approve_all_urgent')
                    ->label('Approve All Urgent')
                    ->icon('heroicon-m-bolt')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Approve All Urgent Requests')
                    ->modalDescription('This will approve all requests with start date within 3 days.')
                    ->action(function () {
                        $urgentRequests = PermohonanCuti::where('status', 'Menunggu')
                            ->whereDate('tanggal_mulai', '<=', now()->addDays(3))
                            ->get();
                            
                        $urgentRequests->each(function ($request) {
                            $request->update([
                                'status' => 'Disetujui',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'approval_note' => 'Auto-approved due to urgency',
                            ]);
                        });
                        
                        Notification::make()
                            ->title('Urgent Requests Approved')
                            ->body($urgentRequests->count() . ' urgent requests have been approved.')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('refresh_queue')
                    ->label('Refresh')
                    ->icon('heroicon-m-arrow-path')
                    ->action(function () {
                        $this->resetTable();
                        Notification::make()
                            ->title('Queue Refreshed')
                            ->body('Approval queue has been refreshed.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->striped()
            ->paginated([10, 15, 25])
            // ->poll() // DISABLED - emergency polling removal
            ->emptyStateHeading('No Pending Approvals')
            ->emptyStateDescription('There are no leave requests pending approval at this time.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->deferLoading();
    }
}