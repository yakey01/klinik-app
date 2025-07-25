<?php

namespace App\Filament\Paramedis\Resources;

use App\Filament\Paramedis\Resources\AttendanceHistoryResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AttendanceHistoryResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = '📊 Laporan Presensi Saya';

    protected static ?string $modelLabel = 'Laporan Presensi';

    protected static ?string $pluralModelLabel = 'Laporan Presensi';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = '📅 PRESENSI & LAPORAN';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan untuk view-only resource
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->width('140px'),

                Tables\Columns\TextColumn::make('time_in')
                    ->label('Check In')
                    ->time('H:i')
                    ->sortable()
                    ->color('success')
                    ->weight('medium')
                    ->width('90px'),

                Tables\Columns\TextColumn::make('time_out')
                    ->label('Check Out')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->color('danger')
                    ->weight('medium')
                    ->width('90px'),

                Tables\Columns\TextColumn::make('total_working_hours')
                    ->label('Total Jam Kerja')
                    ->formatStateUsing(function ($record) {
                        if (!$record->time_in || !$record->time_out) {
                            return '-';
                        }
                        
                        $timeIn = Carbon::parse($record->time_in);
                        $timeOut = Carbon::parse($record->time_out);
                        $duration = $timeOut->diffInMinutes($timeIn);
                        
                        $hours = intval($duration / 60);
                        $minutes = $duration % 60;
                        
                        return sprintf('%d jam %d menit', $hours, $minutes);
                    })
                    ->sortable(false)
                    ->color('primary')
                    ->weight('medium')
                    ->width('150px'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'present',
                        'warning' => 'late',
                        'danger' => 'absent',
                        'info' => 'sick',
                        'secondary' => 'permission',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                        default => $state,
                    })
                    ->width('100px'),

                Tables\Columns\TextColumn::make('location_name_in')
                    ->label('Lokasi')
                    ->formatStateUsing(function ($record) {
                        if ($record->location_name_in) {
                            return $record->location_name_in;
                        }
                        if ($record->latitude && $record->longitude) {
                            return number_format($record->latitude, 4) . ', ' . number_format($record->longitude, 4);
                        }
                        return '-';
                    })
                    ->limit(30)
                    ->tooltip(function ($record) {
                        if ($record->location_name_in) {
                            return $record->location_name_in;
                        }
                        if ($record->latitude && $record->longitude) {
                            return 'Lat: ' . $record->latitude . ', Lng: ' . $record->longitude;
                        }
                        return null;
                    })
                    ->toggleable()
                    ->width('200px'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        'sick' => 'Sakit',
                        'permission' => 'Izin',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereYear('date', Carbon::now()->year)
                        ->whereMonth('date', Carbon::now()->month)
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('last_month')
                    ->label('Bulan Lalu')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereYear('date', Carbon::now()->subMonth()->year)
                        ->whereMonth('date', Carbon::now()->subMonth()->month)
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('custom_date_range')
                    ->label('Rentang Tanggal Custom')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->label('Dari Tanggal')
                                    ->placeholder('Pilih tanggal mulai')
                                    ->default(Carbon::now()->subWeeks(4))
                                    ->maxDate(now()),
                                Forms\Components\DatePicker::make('date_to')
                                    ->label('Sampai Tanggal')
                                    ->placeholder('Pilih tanggal akhir')
                                    ->default(now())
                                    ->maxDate(now()),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'Dari: ' . Carbon::parse($data['date_from'])->format('d/m/Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators['date_to'] = 'Sampai: ' . Carbon::parse($data['date_to'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('incomplete_checkout')
                    ->label('Belum Check Out')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('time_in')->whereNull('time_out'))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter Data')
                    ->icon('heroicon-o-funnel')
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Detail Presensi - ' . $record->date->format('d/m/Y'))
                    ->modalContent(fn ($record) => view('filament.paramedis.modals.attendance-detail', ['record' => $record])),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk view-only
            ])
            ->defaultSort('date', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped()
            ->extremePaginationLinks()
            ->recordUrl(null); // Disable row clicks
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['user'])
            ->select([
                'id', 'user_id', 'date', 'time_in', 'time_out', 
                'status', 'latitude', 'longitude', 'location_name_in',
                'location_name_out', 'notes', 'created_at'
            ])
            ->orderBy('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceHistories::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Show count of attendance records this month
        $count = static::getModel()::where('user_id', auth()->id())
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->count();
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function canCreate(): bool
    {
        return false; // View-only resource
    }

    public static function canEdit($record): bool
    {
        return false; // View-only resource
    }

    public static function canDelete($record): bool
    {
        return false; // View-only resource
    }
}