<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\JadwalJagaResource\Pages;
use App\Models\JadwalJaga;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class JadwalJagaResource extends Resource
{
    protected static ?string $model = JadwalJaga::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'My Schedule';
    protected static ?string $modelLabel = 'Jadwal Jaga';
    protected static ?string $pluralModelLabel = 'Jadwal Jaga Saya';
    protected static ?string $navigationGroup = '📅 Schedule Management';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // Filter jadwal by current user's ID (pegawai_id field)
        return parent::getEloquentQuery()
            ->where('pegawai_id', $user->id)
            ->with(['shiftTemplate', 'pegawai']);
    }

    public static function form(Form $form): Form
    {
        // Read-only for doctors - they can't create/edit schedules
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_jaga')
                    ->label('Tanggal Jaga')
                    ->date('l, d M Y')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('shiftTemplate.nama_shift')
                    ->label('Shift')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('shift_time')
                    ->label('Jam Kerja')
                    ->state(function (JadwalJaga $record): string {
                        if ($record->shiftTemplate) {
                            return $record->shiftTemplate->jam_masuk . ' - ' . $record->shiftTemplate->jam_pulang;
                        }
                        return 'Tidak diketahui';
                    })
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->placeholder('Tidak diisi'),
                    
                Tables\Columns\TextColumn::make('peran')
                    ->label('Peran')
                    ->searchable()
                    ->placeholder('Tidak diisi'),
                    
                Tables\Columns\BadgeColumn::make('status_jaga')
                    ->label('Status')
                    ->colors([
                        'primary' => 'terjadwal',
                        'success' => 'selesai',
                        'warning' => 'berlangsung',
                        'danger' => 'batal',
                    ])
                    ->icons([
                        'terjadwal' => 'heroicon-o-calendar',
                        'selesai' => 'heroicon-o-check-circle',
                        'berlangsung' => 'heroicon-o-clock',
                        'batal' => 'heroicon-o-x-circle',
                    ]),
                    
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->placeholder('Tidak ada keterangan'),
                    
                // Calculate days until schedule
                Tables\Columns\TextColumn::make('days_until')
                    ->label('Selisih Hari')
                    ->state(function (JadwalJaga $record): string {
                        $today = now()->startOfDay();
                        $scheduleDate = $record->tanggal_jaga->startOfDay();
                        $diffInDays = $today->diffInDays($scheduleDate, false);
                        
                        if ($diffInDays > 0) {
                            return '+' . $diffInDays . ' hari';
                        } elseif ($diffInDays < 0) {
                            return $diffInDays . ' hari';
                        } else {
                            return 'Hari ini';
                        }
                    })
                    ->color(function (JadwalJaga $record): string {
                        $today = now()->startOfDay();
                        $scheduleDate = $record->tanggal_jaga->startOfDay();
                        $diffInDays = $today->diffInDays($scheduleDate, false);
                        
                        if ($diffInDays === 0) {
                            return 'primary'; // Today
                        } elseif ($diffInDays > 0 && $diffInDays <= 3) {
                            return 'warning'; // Soon
                        } elseif ($diffInDays > 0) {
                            return 'success'; // Future
                        } else {
                            return 'gray'; // Past
                        }
                    })
                    ->alignment('center'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_jaga')
                    ->label('Status Jaga')
                    ->options([
                        'terjadwal' => 'Terjadwal',
                        'berlangsung' => 'Berlangsung',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),
                    
                Tables\Filters\Filter::make('tanggal_jaga')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_jaga', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_jaga', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('minggu_ini')
                    ->label('Minggu Ini')
                    ->query(function (Builder $query): Builder {
                        return $query->whereBetween('tanggal_jaga', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]);
                    })
                    ->toggle(),
                    
                Tables\Filters\Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('tanggal_jaga', now()->month))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('mendatang')
                    ->label('Jadwal Mendatang')
                    ->query(fn (Builder $query): Builder => $query->where('tanggal_jaga', '>=', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('tanggal_jaga', 'asc')
            ->emptyStateHeading('Belum Ada Jadwal')
            ->emptyStateDescription('Anda belum memiliki jadwal jaga yang terdaftar dalam sistem.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Jadwal')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal_jaga')
                            ->label('Tanggal Jaga')
                            ->date('l, d F Y'),
                            
                        Infolists\Components\TextEntry::make('shiftTemplate.nama_shift')
                            ->label('Nama Shift'),
                            
                        Infolists\Components\TextEntry::make('shift_duration')
                            ->label('Jam Kerja')
                            ->state(function (JadwalJaga $record): string {
                                if ($record->shiftTemplate) {
                                    return $record->shiftTemplate->jam_masuk . ' - ' . $record->shiftTemplate->jam_pulang;
                                }
                                return 'Tidak diketahui';
                            }),
                            
                        Infolists\Components\TextEntry::make('status_jaga')
                            ->label('Status Jaga')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'terjadwal' => 'primary',
                                'selesai' => 'success',
                                'berlangsung' => 'warning',
                                'batal' => 'danger',
                            }),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Detail Lokasi & Peran')
                    ->schema([
                        Infolists\Components\TextEntry::make('unit_kerja')
                            ->label('Unit Kerja')
                            ->placeholder('Tidak diisi'),
                            
                        Infolists\Components\TextEntry::make('peran')
                            ->label('Peran')
                            ->placeholder('Tidak diisi'),
                            
                        Infolists\Components\TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Tidak ada keterangan'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Informasi Shift Template')
                    ->schema([
                        Infolists\Components\TextEntry::make('shiftTemplate.deskripsi')
                            ->label('Deskripsi Shift')
                            ->placeholder('Tidak ada deskripsi'),
                            
                        Infolists\Components\TextEntry::make('shiftTemplate.durasi_jam')
                            ->label('Durasi (Jam)')
                            ->suffix(' jam'),
                            
                        Infolists\Components\TextEntry::make('pegawai.name')
                            ->label('Nama Pegawai'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwalJagas::route('/'),
            'view' => Pages\ViewJadwalJaga::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        
        $upcomingCount = static::getModel()::where('pegawai_id', $user->id)
            ->where('tanggal_jaga', '>=', now())
            ->where('tanggal_jaga', '<=', now()->addWeek())
            ->count();
            
        return $upcomingCount > 0 ? (string) $upcomingCount : null;
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}