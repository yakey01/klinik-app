<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UnifiedFinancialValidationResource extends Resource
{
    protected static ?string $model = Pendapatan::class; // Default to Pendapatan
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Validasi Keuangan';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'financial-validation-center';

    // Dynamic model switching based on tab selection
    public static function getModel(): string
    {
        // Use session to maintain tab state, fallback to request parameter
        $activeTab = session('financial_validation_active_tab', request()->get('activeTab', 'pendapatan'));
        return $activeTab === 'pengeluaran' ? Pengeluaran::class : Pendapatan::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Transaksi Keuangan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Dynamic field based on model type
                                Forms\Components\TextInput::make('transaction_name')
                                    ->label(fn () => static::getModel() === Pendapatan::class ? 'Nama Pendapatan' : 'Nama Pengeluaran')
                                    ->formatStateUsing(function ($record) {
                                        if (!$record) return '';
                                        return $record instanceof Pendapatan 
                                            ? $record->nama_pendapatan 
                                            : $record->nama_pengeluaran;
                                    })
                                    ->disabled(),
                                
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                                    
                                Forms\Components\Select::make('kategori')
                                    ->label('Kategori')
                                    ->options(function () {
                                        $activeTab = session('financial_validation_active_tab', 'pendapatan');
                                        return $activeTab === 'pendapatan' 
                                            ? [
                                                'tindakan_medis' => 'Tindakan Medis',
                                                'obat' => 'Obat',
                                                'konsultasi' => 'Konsultasi',
                                                'lainnya' => 'Lainnya',
                                            ]
                                            : [
                                                'operasional' => 'Operasional',
                                                'medis' => 'Medis',
                                                'administrasi' => 'Administrasi',
                                                'infrastruktur' => 'Infrastruktur',
                                                'lainnya' => 'Lainnya',
                                            ];
                                    })
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->disabled()
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('inputBy.name')
                                    ->label('Input Oleh')
                                    ->disabled(),
                                    
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Waktu Input')
                                    ->disabled(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Informasi Validasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'disetujui' => 'Disetujui',
                                        'ditolak' => 'Ditolak',
                                        'need_revision' => 'Perlu Revisi',
                                    ])
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\TextInput::make('validasiBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak', 'need_revision'])),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('validasi_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak', 'need_revision'])),
                            
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('status_validasi') !== 'pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction_name')
                    ->label('Nama Transaksi')
                    ->formatStateUsing(function ($record) {
                        return $record instanceof Pendapatan 
                            ? $record->nama_pendapatan 
                            : $record->nama_pengeluaran;
                    })
                    ->searchable(['nama_pendapatan', 'nama_pengeluaran'])
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        // Pendapatan categories
                        'tindakan_medis' => 'Tindakan Medis',
                        'obat' => 'Obat',
                        'konsultasi' => 'Konsultasi',
                        // Pengeluaran categories
                        'operasional' => 'Operasional',
                        'medis' => 'Medis',
                        'administrasi' => 'Administrasi',
                        'infrastruktur' => 'Infrastruktur',
                        'lainnya' => 'Lainnya',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'tindakan_medis', 'medis' => 'success',
                        'obat', 'operasional' => 'info',
                        'konsultasi', 'administrasi' => 'warning',
                        'infrastruktur' => 'purple',
                        'lainnya' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ])
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                        'info' => 'need_revision',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'disetujui',
                        'heroicon-o-x-circle' => 'ditolak',
                        'heroicon-o-exclamation-triangle' => 'need_revision',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Validator')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
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
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                    ])
                    ->placeholder('Semua Status'),

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
                            'today' => $query->whereDate('tanggal', today()),
                            'yesterday' => $query->whereDate('tanggal', now()->subDay()),
                            'this_week' => $query->whereBetween('tanggal', [
                                now()->startOfWeek(),
                                now()->endOfWeek()
                            ]),
                            'last_week' => $query->whereBetween('tanggal', [
                                now()->subWeek()->startOfWeek(),
                                now()->subWeek()->endOfWeek()
                            ]),
                            'this_month' => $query->whereMonth('tanggal', now()->month)
                                ->whereYear('tanggal', now()->year),
                            'last_month' => $query->whereMonth('tanggal', now()->subMonth()->month)
                                ->whereYear('tanggal', now()->subMonth()->year),
                            default => $query
                        };
                    }),

                // Value-based Filters  
                Tables\Filters\Filter::make('high_value')
                    ->label('Nilai Tinggi (>1M)')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 1000000))
                    ->toggle(),

                Tables\Filters\Filter::make('very_high_value')
                    ->label('Nilai Sangat Tinggi (>5M)')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 5000000))
                    ->toggle(),

                // Category Filter
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(function () {
                        $activeTab = session('financial_validation_active_tab', 'pendapatan');
                        return $activeTab === 'pengeluaran' 
                            ? [
                                'operasional' => 'Operasional',
                                'medis' => 'Medis',
                                'administrasi' => 'Administrasi',
                                'infrastruktur' => 'Infrastruktur',
                                'lainnya' => 'Lainnya',
                            ]
                            : [
                                'tindakan_medis' => 'Tindakan Medis',
                                'obat' => 'Obat',
                                'konsultasi' => 'Konsultasi',
                                'lainnya' => 'Lainnya',
                            ];
                    }),

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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
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
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('⚡ Quick Approve')
                        ->modalDescription('Approve this transaction without additional comments?')
                        ->modalSubmitActionLabel('Approve')
                        ->action(function (Model $record) {
                            static::quickValidate($record, 'disetujui');
                        }),

                    Action::make('quick_reject')
                        ->label('⚡ Quick Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Please provide reason for rejection...')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'ditolak', $data['rejection_reason']);
                        }),

                    Action::make('approve_with_comment')
                        ->label('✅ Approve with Comment')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('approval_comment')
                                ->label('Approval Comment')
                                ->placeholder('Add validation notes...')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'disetujui', $data['approval_comment'] ?? null);
                        }),

                    Action::make('request_revision')
                        ->label('📝 Request Revision')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('revision_notes')
                                ->label('Revision Notes')
                                ->required()
                                ->placeholder('What needs to be revised?')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'need_revision', $data['revision_notes']);
                        }),

                    // Review Actions (Processed items)
                    Action::make('revert_to_pending')
                        ->label('🔄 Revert to Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => in_array($record->status_validasi, ['disetujui', 'ditolak', 'need_revision']))
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Revert to Pending Status')
                        ->modalDescription('This will return the transaction to pending status for re-validation.')
                        ->modalSubmitActionLabel('Revert')
                        ->form([
                            Forms\Components\Textarea::make('revert_reason')
                                ->label('Revert Reason')
                                ->required()
                                ->placeholder('Why is this being reverted?')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::revertToPending($record, $data['revert_reason']);
                        }),

                    // Universal Actions
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ View Details')
                        ->modalWidth('4xl'),

                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (Model $record): bool => Auth::user()->hasRole(['admin', 'bendahara']))
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
                        ->modalHeading('✅ Bulk Approve Transactions')
                        ->modalDescription('Are you sure you want to approve all selected transactions?')
                        ->modalSubmitActionLabel('Approve All')
                        ->action(function (Collection $records) {
                            static::bulkValidate($records->where('status_validasi', 'pending'), 'disetujui');
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
                                'ditolak',
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
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s') // Real-time updates
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    // Helper Methods for Actions
    protected static function quickValidate(Model $record, string $status, ?string $comment = null): void
    {
        try {
            $record->update([
                'status_validasi' => $status,
                'validasi_by' => Auth::id(),
                'validasi_at' => now(),
                'catatan_validasi' => $comment ?? ($status === 'disetujui' ? 'Quick approved' : 'Quick processed'),
            ]);

            $message = match($status) {
                'disetujui' => 'Transaction berhasil disetujui',
                'ditolak' => 'Transaction berhasil ditolak',
                'need_revision' => 'Revision request sent successfully',
                default => 'Transaction processed successfully'
            };
            
            Notification::make()
                ->title('✅ Success')
                ->body($message)
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
            
            foreach ($records as $record) {
                $record->update([
                    'status_validasi' => $status,
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => $comment ?? "Bulk {$status} by " . Auth::user()->name,
                ]);
            }

            $message = match($status) {
                'disetujui' => "Successfully approved {$count} transactions",
                'ditolak' => "Successfully rejected {$count} transactions",
                default => "Successfully processed {$count} transactions"
            };
            
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

    protected static function revertToPending(Model $record, string $reason): void
    {
        try {
            $record->update([
                'status_validasi' => 'pending',
                'validasi_by' => null,
                'validasi_at' => null,
                'catatan_validasi' => "Reverted by " . Auth::user()->name . ": {$reason}",
            ]);

            Notification::make()
                ->title('🔄 Reverted Successfully')
                ->body('Transaction has been returned to pending status')
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

    public static function getNavigationBadge(): ?string
    {
        $pendingPendapatan = Pendapatan::where('status_validasi', 'pending')->count();
        $pendingPengeluaran = Pengeluaran::where('status_validasi', 'pending')->count();
        $total = $pendingPendapatan + $pendingPengeluaran;
        
        return $total > 0 ? (string) $total : null;
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
            'index' => \App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource\Pages\ListFinancialValidations::route('/'),
            'view' => \App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource\Pages\ViewFinancialValidation::route('/{record}'),
        ];
    }
}