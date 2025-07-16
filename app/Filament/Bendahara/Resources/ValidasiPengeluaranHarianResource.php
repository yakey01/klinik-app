<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\PengeluaranHarian;
use App\Services\ValidationWorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\Alignment;
use Carbon\Carbon;

class ValidasiPengeluaranHarianResource extends Resource
{
    protected static ?string $model = PengeluaranHarian::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    protected static ?string $navigationLabel = '📉 Pengeluaran Harian';

    protected static ?string $modelLabel = 'Pengeluaran Harian';

    protected static ?string $pluralModelLabel = 'Validasi Pengeluaran Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pengeluaran Harian')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_input')
                            ->label('Tanggal Input')
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'pagi' => 'Pagi (07:00-15:00)',
                                'siang' => 'Siang (15:00-23:00)',
                                'malam' => 'Malam (23:00-07:00)',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('pengeluaran_id')
                            ->label('Jenis Pengeluaran')
                            ->relationship('pengeluaran', 'nama_pengeluaran')
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('user_id')
                            ->label('Input Oleh')
                            ->relationship('user', 'name')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validasi Bendahara')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
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
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->color(fn (string $state): string => match ($state) {
                        'pagi' => 'success',
                        'siang' => 'warning',
                        'malam' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pagi' => '🌅 Pagi',
                        'siang' => '🌞 Siang',
                        'malam' => '🌙 Malam',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('pengeluaran.nama_pengeluaran')
                    ->label('Jenis Pengeluaran')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'need_revision' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        'need_revision' => '📝 Revisi',
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('shift')
                    ->label('Shift')
                    ->options([
                        'pagi' => 'Pagi',
                        'siang' => 'Siang',
                        'malam' => 'Malam',
                    ]),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                    ]),

                Tables\Filters\SelectFilter::make('pengeluaran_id')
                    ->label('Jenis Pengeluaran')
                    ->relationship('pengeluaran', 'nama_pengeluaran')
                    ->searchable(),

                Tables\Filters\Filter::make('nominal_besar')
                    ->label('Nominal > 1M')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 1000000)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('approved_amount')
                                ->label('Nominal Disetujui')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(fn (PengeluaranHarian $record) => $record->nominal)
                                ->required(),

                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Catatan Persetujuan')
                                ->placeholder('Tambahkan catatan...')
                                ->rows(3),
                        ])
                        ->action(function (PengeluaranHarian $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'approved',
                                    'nominal' => $data['approved_amount'],
                                    'catatan_validasi' => $data['approval_notes'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('✅ Pengeluaran Disetujui')
                                    ->body("Pengeluaran harian tanggal {$record->tanggal_input->format('d/m/Y')} disetujui")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal Menyetujui')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PengeluaranHarian $record): bool => $record->status_validasi === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
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
                                    'melebihi_budget' => 'Melebihi Budget',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required(),
                        ])
                        ->action(function (PengeluaranHarian $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'rejected',
                                    'catatan_validasi' => $data['rejection_reason'],
                                    'rejection_category' => $data['rejection_category'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('❌ Pengeluaran Ditolak')
                                    ->body("Pengeluaran harian ditolak: {$data['rejection_category']}")
                                    ->warning()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal Menolak')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (PengeluaranHarian $record): bool => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (PengeluaranHarian $record): bool => 
                            in_array($record->status_validasi, ['pending', 'need_revision'])
                        ),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->headerActions([
                Action::make('expense_summary')
                    ->label('📊 Ringkasan Pengeluaran')
                    ->color('danger')
                    ->action(function () {
                        $today = now()->toDateString();
                        $summary = [
                            'total_today' => PengeluaranHarian::whereDate('tanggal_input', $today)->sum('nominal'),
                            'count_today' => PengeluaranHarian::whereDate('tanggal_input', $today)->count(),
                            'pending_count' => PengeluaranHarian::where('status_validasi', 'pending')->count(),
                            'monthly_total' => PengeluaranHarian::whereMonth('tanggal_input', now()->month)
                                ->whereYear('tanggal_input', now()->year)->sum('nominal'),
                        ];

                        $message = "📊 **RINGKASAN PENGELUARAN HARIAN**\n\n";
                        $message .= "📅 Hari Ini: Rp " . number_format($summary['total_today'], 0, ',', '.') . "\n";
                        $message .= "📅 Bulan Ini: Rp " . number_format($summary['monthly_total'], 0, ',', '.') . "\n";
                        $message .= "📝 Total Entry: {$summary['count_today']}\n";
                        $message .= "⏳ Pending: {$summary['pending_count']}";

                        Notification::make()
                            ->title('📊 Ringkasan Pengeluaran')
                            ->body($message)
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['pengeluaran', 'user', 'validasiBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => ValidasiPengeluaranHarianResource\Pages\ListValidasiPengeluaranHarian::route('/'),
        ];
    }
}