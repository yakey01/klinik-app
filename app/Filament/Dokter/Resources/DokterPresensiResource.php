<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\DokterPresensiResource\Pages;
use App\Models\DokterPresensi;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DokterPresensiResource extends Resource
{
    protected static ?string $model = DokterPresensi::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Presensi Saya';
    protected static ?string $modelLabel = 'Presensi';
    protected static ?string $pluralModelLabel = 'Data Presensi';
    protected static ?string $navigationGroup = 'Presensi & Jaspel';
    protected static ?int $navigationSort = 1;

    // Security: Only accessible by dokter role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'dokter';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Presensi')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(Carbon::today())
                            ->disabled(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('jam_masuk')
                                    ->label('Waktu Masuk')
                                    ->disabled(),

                                Forms\Components\TimePicker::make('jam_pulang')
                                    ->label('Waktu Pulang')
                                    ->disabled(),
                            ]),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),

                        Forms\Components\TextInput::make('durasi')
                            ->label('Durasi Kerja')
                            ->disabled(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->placeholder('Masukkan keterangan jika diperlukan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Waktu Masuk')
                    ->time('H:i')
                    ->badge()
                    ->color('info')
                    ->placeholder('Belum Masuk'),

                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Waktu Pulang')
                    ->time('H:i')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->placeholder('Belum Pulang'),

                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi Kerja')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Belum Selesai'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Selesai',
                        'warning' => 'Sedang Bertugas',
                        'danger' => 'Belum Hadir',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Selesai' => '✅ Selesai',
                        'Sedang Bertugas' => '🔄 Sedang Bertugas',
                        'Belum Hadir' => '❌ Belum Hadir',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('tanggal', Carbon::now()->month)
                              ->whereYear('tanggal', Carbon::now()->year)
                    )
                    ->default(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('tanggal', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                    ),

                Filter::make('incomplete')
                    ->label('Belum Pulang')
                    ->query(fn (Builder $query): Builder => $query->whereNull('jam_pulang')),
            ])
            ->actions([
                Action::make('checkin')
                    ->label('🔆 Absen Masuk')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn ($record) => is_null($record->jam_masuk))
                    ->action(function ($record) {
                        $now = Carbon::now();
                        
                        $record->update([
                            'jam_masuk' => $now->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('✅ Absen Masuk Berhasil')
                            ->body("Presensi masuk tercatat pada {$now->format('H:i')}")
                            ->success()
                            ->send();
                    }),

                Action::make('checkout')
                    ->label('🌙 Absen Pulang')
                    ->icon('heroicon-m-stop')
                    ->color('warning')
                    ->visible(fn ($record) => $record->jam_masuk && is_null($record->jam_pulang))
                    ->action(function ($record) {
                        $now = Carbon::now();
                        
                        $record->update([
                            'jam_pulang' => $now->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('✅ Absen Pulang Berhasil')
                            ->body("Presensi pulang tercatat pada {$now->format('H:i')}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status !== 'Selesai'),
            ])
            ->headerActions([
                Action::make('checkin_today')
                    ->label('⏺ Absen Masuk Hari Ini')
                    ->icon('heroicon-m-play-circle')
                    ->color('success')
                    ->visible(function () {
                        $today = Carbon::today();
                        $currentDokter = static::getCurrentDokter();
                        
                        if (!$currentDokter) return false;
                        
                        return !DokterPresensi::where('dokter_id', $currentDokter->id)
                            ->where('tanggal', $today)
                            ->exists();
                    })
                    ->action(function () {
                        $today = Carbon::today();
                        $now = Carbon::now();
                        $currentDokter = static::getCurrentDokter();

                        if (!$currentDokter) {
                            Notification::make()
                                ->title('❌ Error')
                                ->body('Data dokter tidak ditemukan')
                                ->danger()
                                ->send();
                            return;
                        }

                        DokterPresensi::create([
                            'dokter_id' => $currentDokter->id,
                            'tanggal' => $today,
                            'jam_masuk' => $now->format('H:i:s'),
                        ]);

                        Notification::make()
                            ->title('✅ Absen Masuk Berhasil')
                            ->body("Presensi masuk tercatat pada {$now->format('H:i')}")
                            ->success()
                            ->send();
                    }),

                Action::make('checkout_today')
                    ->label('⏹ Absen Pulang Hari Ini')
                    ->icon('heroicon-m-stop-circle')
                    ->color('warning')
                    ->visible(function () {
                        $today = Carbon::today();
                        $currentDokter = static::getCurrentDokter();
                        
                        if (!$currentDokter) return false;
                        
                        return DokterPresensi::where('dokter_id', $currentDokter->id)
                            ->where('tanggal', $today)
                            ->whereNotNull('jam_masuk')
                            ->whereNull('jam_pulang')
                            ->exists();
                    })
                    ->action(function () {
                        $today = Carbon::today();
                        $currentDokter = static::getCurrentDokter();

                        if (!$currentDokter) return;

                        $presensi = DokterPresensi::where('dokter_id', $currentDokter->id)
                            ->where('tanggal', $today)
                            ->whereNotNull('jam_masuk')
                            ->whereNull('jam_pulang')
                            ->first();

                        if ($presensi) {
                            $now = Carbon::now();
                            $presensi->update([
                                'jam_pulang' => $now->format('H:i:s'),
                            ]);

                            Notification::make()
                                ->title('✅ Absen Pulang Berhasil')
                                ->body("Presensi pulang tercatat pada {$now->format('H:i')}")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        $currentDokter = static::getCurrentDokter();
        
        // Security: Only show current doctor's attendance records
        return parent::getEloquentQuery()
            ->when($currentDokter, fn($query) => $query->where('dokter_id', $currentDokter->id))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getCurrentDokter(): ?Dokter
    {
        if (!auth()->check()) {
            return null;
        }

        return Dokter::where('user_id', auth()->id())->first();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDokterPresensis::route('/'),
            'create' => Pages\CreateDokterPresensi::route('/create'),
            'view' => Pages\ViewDokterPresensi::route('/{record}'),
            'edit' => Pages\EditDokterPresensi::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Disable manual creation, use check-in buttons instead
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status !== 'Selesai'; // Can only edit if not completed
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Disable deletion for audit purposes
    }
}