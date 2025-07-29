<?php

namespace App\Filament\Bendahara\Resources;

use App\Filament\Bendahara\Resources\ValidationQueueResource\Pages;
use App\Models\BendaharaValidationQueue;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Services\TindakanValidationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class ValidationQueueResource extends Resource
{
    protected static ?string $model = BendaharaValidationQueue::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationLabel = 'Validasi Queue';
    
    protected static ?string $modelLabel = 'Validation Queue';
    
    protected static ?string $navigationGroup = '📋 Validasi Management';
    
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['bendahara', 'admin', 'supervisor']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['bendahara', 'admin', 'supervisor']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Validation Details')
                    ->schema([
                        Forms\Components\TextInput::make('validation_type')
                            ->label('Validation Type')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('Rp')
                            ->disabled(),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal', 
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'assigned' => 'Assigned',
                                'in_review' => 'In Review',
                                'completed' => 'Completed',
                                'rejected' => 'Rejected',
                            ])
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Related Record')
                    ->schema([
                        Forms\Components\Placeholder::make('related_record')
                            ->label('Related Record')
                            ->content(function (Model $record): string {
                                if ($record->validation_type === 'tindakan') {
                                    $tindakan = Tindakan::find($record->validatable_id);
                                    return $tindakan ? 
                                        "Tindakan: {$tindakan->jenisTindakan->nama} - {$tindakan->formatted_tarif}" :
                                        'Record not found';
                                } elseif ($record->validation_type === 'pendapatan_harian') {
                                    $pendapatan = PendapatanHarian::find($record->validatable_id);
                                    return $pendapatan ? 
                                        "Pendapatan: {$pendapatan->pendapatan->nama_pendapatan} - {$pendapatan->formatted_nominal}" :
                                        'Record not found';
                                }
                                return 'Unknown type';
                            }),
                    ]),
                
                Forms\Components\Section::make('Notes & Comments')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Submission Notes')
                            ->rows(3)
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('validation_notes')
                            ->label('Validation Notes')
                            ->rows(3)
                            ->placeholder('Add your validation notes here...')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('📋 Validation Queue Management')
            ->description('Manage validation requests from petugas for tindakan and pendapatan harian')
            ->columns([
                Tables\Columns\TextColumn::make('validation_type')
                    ->label('📝 Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tindakan' => 'success',
                        'pendapatan_harian' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tindakan' => '🏥 Tindakan',
                        'pendapatan_harian' => '💰 Pendapatan',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('validatable_record')
                    ->label('📄 Record Details')
                    ->formatStateUsing(function (Model $record): string {
                        if ($record->validation_type === 'tindakan') {
                            $tindakan = Tindakan::find($record->validatable_id);
                            return $tindakan ? 
                                $tindakan->jenisTindakan->nama ?? 'Unknown Tindakan' :
                                'Record not found';
                        } elseif ($record->validation_type === 'pendapatan_harian') {
                            $pendapatan = PendapatanHarian::find($record->validatable_id);
                            return $pendapatan ? 
                                $pendapatan->pendapatan->nama_pendapatan ?? 'Unknown Pendapatan' :
                                'Record not found';
                        }
                        return 'Unknown';
                    })
                    ->limit(30)
                    ->tooltip(function (Model $record): ?string {
                        if ($record->validation_type === 'tindakan') {
                            $tindakan = Tindakan::find($record->validatable_id);
                            return $tindakan ? $tindakan->jenisTindakan->nama : null;
                        } elseif ($record->validation_type === 'pendapatan_harian') {
                            $pendapatan = PendapatanHarian::find($record->validatable_id);
                            return $pendapatan ? $pendapatan->pendapatan->nama_pendapatan : null;
                        }
                        return null;
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('💰 Amount')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('⚡ Priority')
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'blue',
                        'high' => 'orange',
                        'urgent' => 'red',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'low' => 'heroicon-o-minus',
                        'normal' => 'heroicon-o-equals',
                        'high' => 'heroicon-o-plus',
                        'urgent' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('📊 Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'assigned' => 'info',
                        'in_review' => 'primary',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'assigned' => 'heroicon-o-user',
                        'in_review' => 'heroicon-o-eye',
                        'completed' => 'heroicon-o-check',
                        'rejected' => 'heroicon-o-x-mark',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Pending',
                        'assigned' => '👤 Assigned',
                        'in_review' => '👁️ In Review',
                        'completed' => '✅ Completed',
                        'rejected' => '❌ Rejected',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('submittedBy.name')
                    ->label('👤 Submitted By')
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('👥 Assigned To')
                    ->placeholder('Not assigned')
                    ->icon('heroicon-o-user-plus')
                    ->color('blue'),
                    
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('📅 Submitted')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->size('sm'),
                    
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('✅ Completed')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Not completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('validation_type')
                    ->label('📝 Validation Type')
                    ->options([
                        'tindakan' => '🏥 Tindakan',
                        'pendapatan_harian' => '💰 Pendapatan Harian',
                    ])
                    ->placeholder('All Types'),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('📊 Status')
                    ->options([
                        'pending' => '⏳ Pending',
                        'assigned' => '👤 Assigned',
                        'in_review' => '👁️ In Review',
                        'completed' => '✅ Completed',
                        'rejected' => '❌ Rejected',
                    ])
                    ->placeholder('All Status'),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('⚡ Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->placeholder('All Priorities'),
                    
                Tables\Filters\Filter::make('submitted_date')
                    ->label('📅 Submission Date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ View Details')
                        ->color('info')
                        ->icon('heroicon-o-eye'),
                    
                    // Assign to self
                    Tables\Actions\Action::make('assign_to_self')
                        ->label('👤 Assign to Me')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible(fn (Model $record): bool => 
                            $record->status === 'pending' && 
                            !$record->assigned_to
                        )
                        ->action(function (Model $record) {
                            $record->update([
                                'status' => 'assigned',
                                'assigned_to' => auth()->id(),
                            ]);
                            
                            Notification::make()
                                ->title('✅ Successfully Assigned')
                                ->body('Validation queue has been assigned to you')
                                ->success()
                                ->send();
                        }),
                    
                    // Start Review
                    Tables\Actions\Action::make('start_review')
                        ->label('👁️ Start Review')
                        ->icon('heroicon-o-eye')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => 
                            $record->status === 'assigned' && 
                            $record->assigned_to === auth()->id()
                        )
                        ->action(function (Model $record) {
                            $record->update(['status' => 'in_review']);
                            
                            Notification::make()
                                ->title('👁️ Review Started')
                                ->body('Validation review has been started')
                                ->success()
                                ->send();
                        }),
                        
                    // Approve Tindakan
                    Tables\Actions\Action::make('approve_tindakan')
                        ->label('✅ Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Model $record): bool => 
                            $record->validation_type === 'tindakan' &&
                            in_array($record->status, ['assigned', 'in_review']) &&
                            $record->assigned_to === auth()->id()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('✅ Approve Tindakan')
                        ->modalDescription('Are you sure you want to approve this tindakan?')
                        ->form([
                            Textarea::make('approval_comment')
                                ->label('Approval Comment (Optional)')
                                ->placeholder('Add approval comment...')
                                ->rows(3),
                        ])
                        ->action(function (Model $record, array $data) {
                            $tindakan = Tindakan::find($record->validatable_id);
                            if ($tindakan) {
                                $validationService = new TindakanValidationService();
                                $result = $validationService->approve($tindakan, $data['approval_comment'] ?? null);
                                
                                Notification::make()
                                    ->title('✅ Tindakan Approved')
                                    ->body('Tindakan has been successfully approved')
                                    ->success()
                                    ->send();
                            }
                        }),
                        
                    // Reject Tindakan
                    Tables\Actions\Action::make('reject_tindakan')
                        ->label('❌ Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Model $record): bool => 
                            $record->validation_type === 'tindakan' &&
                            in_array($record->status, ['assigned', 'in_review']) &&
                            $record->assigned_to === auth()->id()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('❌ Reject Tindakan')
                        ->modalDescription('Please provide a reason for rejection.')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->placeholder('Please explain why this tindakan is being rejected...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Model $record, array $data) {
                            $tindakan = Tindakan::find($record->validatable_id);
                            if ($tindakan) {
                                $validationService = new TindakanValidationService();
                                $result = $validationService->reject($tindakan, $data['rejection_reason']);
                                
                                Notification::make()
                                    ->title('❌ Tindakan Rejected')
                                    ->body('Tindakan has been rejected')
                                    ->success()
                                    ->send();
                            }
                        }),
                        
                    // Approve Pendapatan
                    Tables\Actions\Action::make('approve_pendapatan')
                        ->label('✅ Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Model $record): bool => 
                            $record->validation_type === 'pendapatan_harian' &&
                            in_array($record->status, ['assigned', 'in_review']) &&
                            $record->assigned_to === auth()->id()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('✅ Approve Pendapatan')
                        ->modalDescription('Are you sure you want to approve this pendapatan?')
                        ->form([
                            Textarea::make('approval_comment')
                                ->label('Approval Comment (Optional)')
                                ->placeholder('Add approval comment...')
                                ->rows(3),
                        ])
                        ->action(function (Model $record, array $data) {
                            $pendapatan = PendapatanHarian::find($record->validatable_id);
                            if ($pendapatan) {
                                $pendapatan->update([
                                    'status_validasi' => 'disetujui',
                                    'validasi_by' => auth()->id(),
                                    'validasi_at' => now(),
                                    'catatan_validasi' => $data['approval_comment'] ?? 'Approved by bendahara',
                                ]);
                                
                                $record->update([
                                    'status' => 'completed',
                                    'completed_at' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('✅ Pendapatan Approved')
                                    ->body('Pendapatan has been successfully approved')
                                    ->success()
                                    ->send();
                            }
                        }),
                        
                    // Reject Pendapatan
                    Tables\Actions\Action::make('reject_pendapatan')
                        ->label('❌ Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Model $record): bool => 
                            $record->validation_type === 'pendapatan_harian' &&
                            in_array($record->status, ['assigned', 'in_review']) &&
                            $record->assigned_to === auth()->id()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('❌ Reject Pendapatan')
                        ->modalDescription('Please provide a reason for rejection.')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->placeholder('Please explain why this pendapatan is being rejected...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Model $record, array $data) {
                            $pendapatan = PendapatanHarian::find($record->validatable_id);
                            if ($pendapatan) {
                                $pendapatan->update([
                                    'status_validasi' => 'ditolak',
                                    'validasi_by' => auth()->id(),
                                    'validasi_at' => now(),
                                    'catatan_validasi' => $data['rejection_reason'],
                                ]);
                                
                                $record->update([
                                    'status' => 'rejected',
                                    'completed_at' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Pendapatan Rejected')
                                    ->body('Pendapatan has been rejected')
                                    ->success()
                                    ->send();
                            }
                        }),
                ])
                ->label('⚙️ Actions')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk assign
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('👤 Assign All to Me')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'assigned',
                                        'assigned_to' => auth()->id(),
                                    ]);
                                    $updated++;
                                }
                            }
                            
                            Notification::make()
                                ->title('✅ Bulk Assignment Complete')
                                ->body("Successfully assigned {$updated} records to you")
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->defaultSort('submitted_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s') // Real-time updates
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->emptyStateHeading('📋 No Validation Requests')
            ->emptyStateDescription('There are currently no validation requests in the queue.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['submittedBy', 'assignedTo'])
            ->orderBy('priority', 'desc')
            ->orderBy('submitted_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidationQueues::route('/'),
            'view' => Pages\ViewValidationQueue::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        $assignedCount = static::getModel()::where('assigned_to', auth()->id())
            ->whereIn('status', ['assigned', 'in_review'])
            ->count();
            
        $totalBadge = $pendingCount + $assignedCount;
        
        return $totalBadge > 0 ? (string) $totalBadge : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $urgentCount = static::getModel()::where('priority', 'urgent')
            ->where('status', 'pending')
            ->count();
            
        return $urgentCount > 0 ? 'danger' : 'warning';
    }
}