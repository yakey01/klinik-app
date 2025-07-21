<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Pendapatan;
use App\Models\PendapatanHarian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ValidasiPendapatanResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Validasi Pendapatan';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pendapatan')
                    ->schema([
                        Forms\Components\TextInput::make('nama_pendapatan')
                            ->label('Nama Pendapatan')
                            ->required()
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'tindakan_medis' => 'Tindakan Medis',
                                'obat' => 'Obat',
                                'konsultasi' => 'Konsultasi',
                                'lainnya' => 'Lainnya',
                            ])
                            ->disabled(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('input_by')
                            ->label('Input Oleh')
                            ->relationship('inputBy', 'name')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validasi Bendahara')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'disetujui' => 'Disetujui', 
                                'ditolak' => 'Ditolak',
                                'need_revision' => 'Perlu Revisi',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Jenis Pendapatan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tindakan_medis' => 'Tindakan Medis',
                        'obat' => 'Obat',
                        'konsultasi' => 'Konsultasi',
                        'lainnya' => 'Lainnya',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'tindakan_medis' => 'success',
                        'obat' => 'info',
                        'konsultasi' => 'warning',
                        'lainnya' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        'need_revision' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'need_revision' => 'Revisi',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Validasi Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Tanggal Dari'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Tanggal Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                    ]),

                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'tindakan_medis' => 'Tindakan Medis',
                        'obat' => 'Obat',
                        'konsultasi' => 'Konsultasi',
                        'lainnya' => 'Lainnya',
                    ]),

                Tables\Filters\Filter::make('nominal_besar')
                    ->label('Nominal > 1M')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 1000000)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Setujui')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('approved_amount')
                                ->label('Nominal Disetujui')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(fn (Pendapatan $record) => $record->nominal)
                                ->required(),

                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Catatan Persetujuan')
                                ->placeholder('Tambahkan catatan...')
                                ->rows(3),
                        ])
                        ->action(function (Pendapatan $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'disetujui',
                                    'nominal' => $data['approved_amount'],
                                    'catatan_validasi' => $data['approval_notes'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('Pendapatan Disetujui')
                                    ->body("Pendapatan {$record->nama_pendapatan} disetujui")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Menyetujui')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('Tolak')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),

                            Forms\Components\Select::make('rejection_category')
                                ->label('Kategori Penolakan')
                                ->options([
                                    'nominal_tidak_sesuai' => 'Nominal Tidak Sesuai',
                                    'bukti_tidak_valid' => 'Bukti Tidak Valid',
                                    'kategori_salah' => 'Kategori Salah',
                                    'duplikasi' => 'Data Duplikasi',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required(),
                        ])
                        ->action(function (Pendapatan $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'ditolak',
                                    'catatan_validasi' => $data['rejection_reason'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('Pendapatan Ditolak')
                                    ->body("Pendapatan ditolak: {$data['rejection_category']}")
                                    ->warning()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Menolak')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->visible(fn (Pendapatan $record): bool => 
                            in_array($record->status_validasi, ['pending', 'need_revision'])
                        ),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->headerActions([
                Action::make('revenue_summary')
                    ->label('Ringkasan Pendapatan')
                    ->color('success')
                    ->action(function () {
                        $today = now()->toDateString();
                        $summary = [
                            'total_today' => Pendapatan::whereDate('tanggal', $today)->sum('nominal'),
                            'count_today' => Pendapatan::whereDate('tanggal', $today)->count(),
                            'pending_count' => Pendapatan::where('status_validasi', 'pending')->count(),
                            'monthly_total' => Pendapatan::whereMonth('tanggal', now()->month)
                                ->whereYear('tanggal', now()->year)->sum('nominal'),
                        ];

                        $message = "RINGKASAN PENDAPATAN\n\n";
                        $message .= "Hari Ini: Rp " . number_format($summary['total_today'], 0, ',', '.') . "\n";
                        $message .= "Bulan Ini: Rp " . number_format($summary['monthly_total'], 0, ',', '.') . "\n";
                        $message .= "Total Entry: {$summary['count_today']}\n";
                        $message .= "Pending: {$summary['pending_count']}";

                        Notification::make()
                            ->title('Ringkasan Pendapatan')
                            ->body($message)
                            ->info()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inputBy', 'validasiBy', 'tindakan'])
            ->whereNotNull('input_by');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Always show in navigation
    }
    
    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages\ListValidasiPendapatan::route('/'),
        ];
    }
}