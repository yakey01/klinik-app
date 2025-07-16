<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\PendapatanHarian;
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

class ValidasiPendapatanHarianResource extends Resource
{
    protected static ?string $model = PendapatanHarian::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    protected static ?string $navigationLabel = '📈 Pendapatan Harian';

    protected static ?string $modelLabel = 'Pendapatan Harian';

    protected static ?string $pluralModelLabel = 'Validasi Pendapatan Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pendapatan Harian')
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

                        Forms\Components\Select::make('pendapatan_id')
                            ->label('Jenis Pendapatan')
                            ->relationship('pendapatan', 'nama_pendapatan')
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

                Tables\Columns\TextColumn::make('pendapatan.nama_pendapatan')
                    ->label('Jenis Pendapatan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

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

                Tables\Filters\SelectFilter::make('pendapatan_id')
                    ->label('Jenis Pendapatan')
                    ->relationship('pendapatan', 'nama_pendapatan')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->action(function (PendapatanHarian $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'approved',
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('✅ Pendapatan Disetujui')
                                    ->body("Pendapatan harian tanggal {$record->tanggal_input->format('d/m/Y')} disetujui")
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
                        ->visible(fn (PendapatanHarian $record): bool => $record->status_validasi === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (PendapatanHarian $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'rejected',
                                    'catatan_validasi' => $data['rejection_reason'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('❌ Pendapatan Ditolak')
                                    ->body("Pendapatan harian ditolak")
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
                        ->visible(fn (PendapatanHarian $record): bool => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (PendapatanHarian $record): bool => 
                            in_array($record->status_validasi, ['pending', 'need_revision'])
                        ),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->headerActions([
                Action::make('daily_summary')
                    ->label('📊 Ringkasan Harian')
                    ->color('info')
                    ->action(function () {
                        $today = now()->toDateString();
                        $summary = [
                            'total_today' => PendapatanHarian::whereDate('tanggal_input', $today)->sum('nominal'),
                            'count_today' => PendapatanHarian::whereDate('tanggal_input', $today)->count(),
                            'pending_count' => PendapatanHarian::where('status_validasi', 'pending')->count(),
                        ];

                        $message = "📊 **RINGKASAN PENDAPATAN HARIAN**\n\n";
                        $message .= "📅 Hari Ini: Rp " . number_format($summary['total_today'], 0, ',', '.') . "\n";
                        $message .= "📝 Total Entry: {$summary['count_today']}\n";
                        $message .= "⏳ Pending: {$summary['pending_count']}";

                        Notification::make()
                            ->title('📊 Ringkasan Pendapatan')
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
            ->with(['pendapatan', 'user', 'validasiBy']);
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
            'index' => ValidasiPendapatanHarianResource\Pages\ListValidasiPendapatanHarian::route('/'),
        ];
    }
}