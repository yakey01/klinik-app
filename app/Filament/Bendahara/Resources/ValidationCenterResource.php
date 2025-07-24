<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ValidationCenterResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Validasi Tindakan';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'validation-center';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Validation Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jenisTindakan.nama')
                                    ->label('Jenis Tindakan')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('pasien.nama')
                                    ->label('Nama Pasien')
                                    ->disabled(),
                                    
                                Forms\Components\DateTimePicker::make('tanggal_tindakan')
                                    ->label('Tanggal Tindakan')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('tarif')
                                    ->label('Tarif')
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('jasa_dokter')
                                    ->label('Jasa Dokter')
                                    ->prefix('Rp')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('jasa_paramedis')
                                    ->label('Jasa Paramedis')
                                    ->prefix('Rp')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('jasa_non_paramedis')
                                    ->label('Jasa Non-Paramedis')
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tindakan')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Validation Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                    ])
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\TextInput::make('validatedBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['approved', 'rejected'])),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('validated_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['approved', 'rejected'])),
                            
                        Forms\Components\Textarea::make('komentar_validasi')
                            ->label('Komentar Validasi')
                            ->placeholder('Tambahkan komentar validasi...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('status_validasi') !== 'pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(20)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('jaspel_diterima')
                    ->label('Jaspel Diterima')
                    ->money('IDR')
                    ->sortable()
                    ->getStateUsing(function (Tindakan $record): float {
                        // Calculate Jaspel based on JenisTindakan percentage
                        $persentaseJaspel = $record->jenisTindakan->persentase_jaspel ?? 40;
                        return $record->tarif * ($persentaseJaspel / 100);
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Jaspel')
                            ->using(function ($query) {
                                // Calculate total jaspel based on tarif and percentage
                                $tindakanRecords = \App\Models\Tindakan::whereIn('id', $query->pluck('id'))
                                    ->with('jenisTindakan')
                                    ->get();
                                    
                                $total = $tindakanRecords->sum(function ($record) {
                                    $persentaseJaspel = $record->jenisTindakan->persentase_jaspel ?? 40;
                                    return $record->tarif * ($persentaseJaspel / 100);
                                });
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pelaksana_tindakan')
                    ->label('Pelaksana Tindakan')
                    ->getStateUsing(function (Tindakan $record): string {
                        $pelaksana = [];
                        if ($record->dokter) {
                            $pelaksana[] = 'Dr. ' . $record->dokter->nama_lengkap;
                        }
                        if ($record->paramedis) {
                            $pelaksana[] = $record->paramedis->nama_lengkap . ' (Paramedis)';
                        }
                        if ($record->nonParamedis) {
                            $pelaksana[] = $record->nonParamedis->nama_lengkap . ' (Non-Paramedis)';
                        }
                        return empty($pelaksana) ? '-' : implode(', ', $pelaksana);
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Petugas Input')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Validator')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick Status Filters
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui', 
                        'rejected' => 'Ditolak',
                    ])
                    ->placeholder('All Status'),

                // Quick Date Range Filters
                Tables\Filters\SelectFilter::make('date_range')
                    ->label('Periode')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                        'last_week' => 'Minggu Lalu',
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Lalu',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) return $query;
                        
                        return match ($data['value']) {
                            'today' => $query->whereDate('tanggal_tindakan', today()),
                            'yesterday' => $query->whereDate('tanggal_tindakan', yesterday()),
                            'this_week' => $query->whereBetween('tanggal_tindakan', [
                                now()->startOfWeek(),
                                now()->endOfWeek()
                            ]),
                            'last_week' => $query->whereBetween('tanggal_tindakan', [
                                now()->subWeek()->startOfWeek(),
                                now()->subWeek()->endOfWeek()
                            ]),
                            'this_month' => $query->whereMonth('tanggal_tindakan', now()->month)
                                ->whereYear('tanggal_tindakan', now()->year),
                            'last_month' => $query->whereMonth('tanggal_tindakan', now()->subMonth()->month)
                                ->whereYear('tanggal_tindakan', now()->subMonth()->year),
                            default => $query
                        };
                    }),

                // Value-based Filters
                Tables\Filters\Filter::make('high_value')
                    ->label('High Value (>500K)')
                    ->query(fn (Builder $query): Builder => $query->where('tarif', '>', 500000))
                    ->toggle(),

                Tables\Filters\Filter::make('very_high_value')
                    ->label('Very High Value (>1M)')
                    ->query(fn (Builder $query): Builder => $query->where('tarif', '>', 1000000))
                    ->toggle(),

                // Validator Filter
                Tables\Filters\SelectFilter::make('validated_by')
                    ->label('Validator')
                    ->relationship('validatedBy', 'name')
                    ->searchable()
                    ->preload(),

                // Custom Date Range Filter
                Tables\Filters\Filter::make('custom_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // Quick Validation Actions (Pending only)
                    Action::make('quick_approve')
                        ->label('⚡ Quick Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('⚡ Quick Approve')
                        ->modalDescription('Approve this tindakan without additional comments?')
                        ->modalSubmitActionLabel('Approve')
                        ->action(function (Tindakan $record) {
                            static::quickValidate($record, 'approved');
                        }),

                    Action::make('quick_reject')
                        ->label('⚡ Quick Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Please provide reason for rejection...')
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            static::quickValidate($record, 'rejected', $data['rejection_reason']);
                        }),

                    Action::make('approve_with_comment')
                        ->label('✅ Approve with Comment')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('approval_comment')
                                ->label('Approval Comment')
                                ->placeholder('Add validation notes...')
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            static::quickValidate($record, 'approved', $data['approval_comment'] ?? null);
                        }),

                    // Review Actions (Processed items)
                    Action::make('revert_to_pending')
                        ->label('🔄 Revert to Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Tindakan $record): bool => in_array($record->status_validasi, ['approved', 'rejected']))
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Revert to Pending')
                        ->modalDescription('This will return the tindakan to pending status for re-validation.')
                        ->modalSubmitActionLabel('Revert')
                        ->form([
                            Forms\Components\Textarea::make('revert_reason')
                                ->label('Revert Reason')
                                ->required()
                                ->placeholder('Why is this being reverted?')
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            static::revertToPending($record, $data['revert_reason']);
                        }),

                    // Universal Actions
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ View Details')
                        ->modalWidth('4xl'),

                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (Tindakan $record): bool => Auth::user()->hasRole(['admin', 'bendahara']))
                        ->modalWidth('4xl'),
                ])
                ->label('⚙️ Actions')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Validation Actions
                    BulkAction::make('bulk_approve')
                        ->label('✅ Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('✅ Bulk Approve Tindakan')
                        ->modalDescription('Are you sure you want to approve all selected tindakan?')
                        ->modalSubmitActionLabel('Approve All')
                        ->action(function (Collection $records) {
                            static::bulkValidate($records->where('status_validasi', 'pending'), 'approved');
                        }),

                    BulkAction::make('bulk_reject')
                        ->label('❌ Bulk Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('bulk_rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Provide reason for bulk rejection...')
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkValidate(
                                $records->where('status_validasi', 'pending'),
                                'rejected',
                                $data['bulk_rejection_reason']
                            );
                        }),

                    // Export Actions
                    BulkAction::make('export_selected')
                        ->label('📤 Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('export_format')
                                ->label('Export Format')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'pdf' => 'PDF (.pdf)'
                                ])
                                ->default('xlsx')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::exportRecords($records, $data['export_format']);
                        }),

                    // Bulk Assignment
                    BulkAction::make('bulk_assign_validator')
                        ->label('👤 Assign Validator')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('validator_id')
                                ->label('Assign to Validator')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'bendahara');
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkAssignValidator($records, $data['validator_id']);
                        }),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s') // Real-time updates
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('input_by')
            ->with([
                'jenisTindakan',
                'pasien',
                'dokter',
                'paramedis',
                'nonParamedis',
                'inputBy',
                'validatedBy',
                'jaspel'
            ]);
    }

    // Helper Methods for Actions
    protected static function quickValidate(Tindakan $record, string $status, ?string $comment = null): void
    {
        try {
            // Use database transaction for consistency
            \DB::transaction(function() use ($record, $status, $comment) {
                // Map validation status to consistent format
                $mappedStatus = $status === 'approved' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : $status);
                
                $record->update([
                    'status_validasi' => $mappedStatus,
                    'status' => $mappedStatus === 'disetujui' ? 'selesai' : 'batal',
                    'validated_by' => Auth::id(),
                    'validated_at' => now(),
                    'komentar_validasi' => $comment ?? ($mappedStatus === 'disetujui' ? 'Quick approved' : 'Quick rejected'),
                ]);

                // Update or create related Jaspel records with matching status
                if ($mappedStatus === 'disetujui') {
                    try {
                        $jaspelService = app(\App\Services\JaspelCalculationService::class);
                        $createdJaspel = $jaspelService->calculateJaspelFromTindakan($record);
                        
                        // Update newly created Jaspel records to 'disetujui' status
                        if (is_array($createdJaspel)) {
                            foreach ($createdJaspel as $jaspel) {
                                if ($jaspel instanceof \App\Models\Jaspel) {
                                    $jaspel->update([
                                        'status_validasi' => 'disetujui',
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                        'catatan_validasi' => 'Auto-approved with Tindakan validation'
                                    ]);
                                }
                            }
                        }
                        
                        $jaspelCount = is_array($createdJaspel) ? count($createdJaspel) : 0;
                        $GLOBALS['validation_message'] = "Tindakan berhasil disetujui dan {$jaspelCount} record Jaspel dibuat & disetujui";
                    } catch (\Exception $jaspelError) {
                        \Log::warning('Failed to auto-generate Jaspel: ' . $jaspelError->getMessage());
                        $GLOBALS['validation_message'] = 'Tindakan berhasil disetujui (Jaspel akan digenerate manual)';
                    }
                } else {
                    // If rejected, also reject existing Jaspel records
                    $record->jaspel()->update([
                        'status_validasi' => 'ditolak',
                        'validasi_by' => Auth::id(),
                        'validasi_at' => now(),
                        'catatan_validasi' => 'Rejected due to Tindakan rejection: ' . ($comment ?? 'Quick rejected')
                    ]);
                    
                    $GLOBALS['validation_message'] = 'Tindakan berhasil ditolak dan Jaspel terkait diperbarui';
                }
            });
            
            Notification::make()
                ->title('✅ Success')
                ->body($GLOBALS['validation_message'] ?? 'Validasi berhasil')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body('Validation failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function bulkValidate(Collection $records, string $status, ?string $comment = null): void
    {
        try {
            $count = $records->count();
            
            \DB::transaction(function() use ($records, $status, $comment) {
                foreach ($records as $record) {
                    // Map validation status to consistent format
                    $mappedStatus = $status === 'approved' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : $status);
                    
                    $record->update([
                        'status_validasi' => $mappedStatus,
                        'status' => $mappedStatus === 'disetujui' ? 'selesai' : 'batal',
                        'validated_by' => Auth::id(),
                        'validated_at' => now(),
                        'komentar_validasi' => $comment ?? "Bulk {$mappedStatus} by " . Auth::user()->name,
                    ]);

                    // Handle Jaspel synchronization
                    if ($mappedStatus === 'disetujui') {
                        try {
                            $jaspelService = app(\App\Services\JaspelCalculationService::class);
                            $createdJaspel = $jaspelService->calculateJaspelFromTindakan($record);
                            
                            // Update newly created Jaspel records to 'disetujui' status
                            if (is_array($createdJaspel)) {
                                foreach ($createdJaspel as $jaspel) {
                                    if ($jaspel instanceof \App\Models\Jaspel) {
                                        $jaspel->update([
                                            'status_validasi' => 'disetujui',
                                            'validasi_by' => Auth::id(),
                                            'validasi_at' => now(),
                                            'catatan_validasi' => 'Auto-approved with bulk Tindakan validation'
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $jaspelError) {
                            \Log::warning('Failed to auto-generate Jaspel for bulk operation: ' . $jaspelError->getMessage());
                        }
                    } else {
                        // If rejected, also reject existing Jaspel records
                        $record->jaspel()->update([
                            'status_validasi' => 'ditolak',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                            'catatan_validasi' => 'Rejected due to bulk Tindakan rejection: ' . ($comment ?? 'Bulk rejected')
                        ]);
                    }
                }
            });

            $message = $status === 'approved' 
                ? "Successfully approved {$count} tindakan and synchronized Jaspel records"
                : "Successfully rejected {$count} tindakan and updated related Jaspel records";
            
            Notification::make()
                ->title('✅ Bulk Operation Complete')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Bulk Operation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function revertToPending(Tindakan $record, string $reason): void
    {
        try {
            \DB::transaction(function() use ($record, $reason) {
                $record->update([
                    'status_validasi' => 'pending',
                    'status' => 'pending',
                    'validated_by' => null,
                    'validated_at' => null,
                    'komentar_validasi' => "Reverted by " . Auth::user()->name . ": {$reason}",
                ]);

                // Also revert related Jaspel records to pending
                $record->jaspel()->update([
                    'status_validasi' => 'pending',
                    'validasi_by' => null,
                    'validasi_at' => null,
                    'catatan_validasi' => "Reverted due to Tindakan revert: {$reason}"
                ]);
            });

            Notification::make()
                ->title('🔄 Reverted Successfully')
                ->body('Tindakan and related Jaspel records have been returned to pending status')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Revert Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function exportRecords(Collection $records, string $format): void
    {
        // Export functionality placeholder
        Notification::make()
            ->title('📤 Export Initiated')
            ->body("Exporting {$records->count()} records to {$format} format")
            ->info()
            ->send();
    }

    protected static function bulkAssignValidator(Collection $records, int $validatorId): void
    {
        try {
            $validator = \App\Models\User::find($validatorId);
            $count = $records->count();
            
            // For now, just add a comment about assignment
            foreach ($records as $record) {
                $currentComment = $record->komentar_validasi ?? '';
                $assignmentNote = "Assigned to {$validator->name} by " . Auth::user()->name . " on " . now()->format('d/m/Y H:i');
                
                $record->update([
                    'komentar_validasi' => $currentComment ? "{$currentComment}\n{$assignmentNote}" : $assignmentNote,
                ]);
            }

            Notification::make()
                ->title('👤 Assignment Complete')
                ->body("Assigned {$count} tindakan to {$validator->name}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Assignment Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status_validasi', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['admin', 'bendahara']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\ValidationCenterResource\Pages\ListValidations::route('/'),
            'view' => \App\Filament\Bendahara\Resources\ValidationCenterResource\Pages\ViewValidation::route('/{record}'),
        ];
    }
}