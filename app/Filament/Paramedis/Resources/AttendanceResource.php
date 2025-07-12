<?php

namespace App\Filament\Paramedis\Resources;

use App\Filament\Paramedis\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\User;
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

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Presensi Saya';
    protected static ?string $modelLabel = 'Presensi';
    protected static ?string $pluralModelLabel = 'Data Presensi';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?int $navigationSort = 1;

    // Security: Only accessible by paramedis role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Presensi')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->default(Carbon::today())
                            ->disabled(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('time_in')
                                    ->label('Waktu Masuk')
                                    ->disabled(),

                                Forms\Components\TimePicker::make('time_out')
                                    ->label('Waktu Pulang')
                                    ->disabled(),
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'present' => 'Hadir',
                                'late' => 'Terlambat',
                                'incomplete' => 'Belum Pulang',
                            ])
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_in')
                    ->label('Waktu Masuk')
                    ->time('H:i')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('time_out')
                    ->label('Waktu Pulang')
                    ->time('H:i')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->placeholder('Belum Pulang'),

                Tables\Columns\TextColumn::make('formatted_work_duration')
                    ->label('Durasi Kerja')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Belum Selesai'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'present',
                        'warning' => 'late',
                        'danger' => 'incomplete',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => '✅ Hadir',
                        'late' => '⚠️ Terlambat',
                        'incomplete' => '🔄 Belum Pulang',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('location_name_in')
                    ->label('Lokasi')
                    ->limit(20)
                    ->placeholder('Tidak ada'),
            ])
            ->filters([
                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('date', Carbon::now()->month)
                              ->whereYear('date', Carbon::now()->year)
                    )
                    ->default(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                    ),

                Filter::make('incomplete')
                    ->label('Belum Pulang')
                    ->query(fn (Builder $query): Builder => $query->whereNull('time_out')),
            ])
            ->actions([
                Action::make('checkin')
                    ->label('🔆 Absen Masuk')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn ($record) => is_null($record->time_in))
                    ->action(function ($record) {
                        // Check if already checked in today
                        $today = Carbon::today();
                        $existingAttendance = Attendance::where('user_id', auth()->id())
                            ->where('date', $today)
                            ->first();

                        if ($existingAttendance) {
                            Notification::make()
                                ->title('❌ Sudah Absen Masuk')
                                ->body('Anda sudah melakukan absen masuk hari ini')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Create new attendance record
                        $now = Carbon::now();
                        $workStartTime = Carbon::createFromTime(8, 0, 0);
                        $status = $now->gt($workStartTime) ? 'late' : 'present';

                        Attendance::create([
                            'user_id' => auth()->id(),
                            'date' => $today,
                            'time_in' => $now->format('H:i:s'),
                            'status' => $status,
                            'location_name_in' => 'Klinik Dokterku',
                            'notes' => 'Check-in melalui Dashboard Paramedis'
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
                    ->visible(fn ($record) => $record->time_in && is_null($record->time_out))
                    ->action(function ($record) {
                        $now = Carbon::now();
                        
                        $record->update([
                            'time_out' => $now->format('H:i:s'),
                            'location_name_out' => 'Klinik Dokterku',
                            'status' => 'present',
                            'notes' => $record->notes . "\nCheck-out melalui Dashboard Paramedis"
                        ]);

                        Notification::make()
                            ->title('✅ Absen Pulang Berhasil')
                            ->body("Presensi pulang tercatat pada {$now->format('H:i')}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Action::make('checkin_today')
                    ->label('⏺ Absen Masuk Hari Ini')
                    ->icon('heroicon-m-play-circle')
                    ->color('success')
                    ->visible(function () {
                        $today = Carbon::today();
                        return !Attendance::where('user_id', auth()->id())
                            ->where('date', $today)
                            ->exists();
                    })
                    ->action(function () {
                        $today = Carbon::today();
                        $now = Carbon::now();
                        $workStartTime = Carbon::createFromTime(8, 0, 0);
                        $status = $now->gt($workStartTime) ? 'late' : 'present';

                        Attendance::create([
                            'user_id' => auth()->id(),
                            'date' => $today,
                            'time_in' => $now->format('H:i:s'),
                            'status' => $status,
                            'location_name_in' => 'Klinik Dokterku',
                            'notes' => 'Check-in melalui Dashboard Paramedis'
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
                        return Attendance::where('user_id', auth()->id())
                            ->where('date', $today)
                            ->whereNotNull('time_in')
                            ->whereNull('time_out')
                            ->exists();
                    })
                    ->action(function () {
                        $today = Carbon::today();
                        $attendance = Attendance::where('user_id', auth()->id())
                            ->where('date', $today)
                            ->whereNotNull('time_in')
                            ->whereNull('time_out')
                            ->first();

                        if ($attendance) {
                            $now = Carbon::now();
                            $attendance->update([
                                'time_out' => $now->format('H:i:s'),
                                'location_name_out' => 'Klinik Dokterku',
                                'status' => 'present',
                                'notes' => $attendance->notes . "\nCheck-out melalui Dashboard Paramedis"
                            ]);

                            Notification::make()
                                ->title('✅ Absen Pulang Berhasil')
                                ->body("Presensi pulang tercatat pada {$now->format('H:i')}")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        // Security: Only show current user's attendance records
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'view' => Pages\ViewAttendance::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Disable manual creation
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Disable editing
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Disable deletion
    }
}
