<?php

namespace App\Filament\Paramedis\Resources\AttendanceResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Cheesegrits\FilamentGoogleMaps\Infolists\MapEntry;

class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->user_id === auth()->id() || auth()->user()->hasRole(['super_admin', 'admin'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Kehadiran')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Pegawai'),
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y l'),
                                Infolists\Components\TextEntry::make('time_in')
                                    ->label('Waktu Masuk')
                                    ->time('H:i'),
                                Infolists\Components\TextEntry::make('time_out')
                                    ->label('Waktu Keluar')
                                    ->time('H:i')
                                    ->placeholder('Belum check-out'),
                                Infolists\Components\TextEntry::make('work_duration')
                                    ->label('Durasi Kerja')
                                    ->formatStateUsing(fn ($record) => $record->formatted_work_duration ?? 'Belum check-out'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'present' => 'success',
                                        'late' => 'warning',
                                        'absent' => 'danger',
                                        'sick' => 'info',
                                        'permission' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'present' => 'Hadir',
                                        'late' => 'Terlambat',
                                        'absent' => 'Tidak Hadir',
                                        'sick' => 'Sakit',
                                        'permission' => 'Izin',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Lokasi Check-in')
                    ->schema([
                        MapEntry::make('checkin_map')
                            ->label('')
                            ->columnSpanFull()
                            ->height('300px')
                            ->zoom(15)
                            ->mapType('roadmap')
                            ->showMarker()
                            ->markerColor('red')
                            ->formatStateUsing(function ($record) {
                                if ($record->latitude && $record->longitude) {
                                    return [
                                        'lat' => (float) $record->latitude,
                                        'lng' => (float) $record->longitude,
                                    ];
                                }
                                return null;
                            }),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('location_name_in')
                                    ->label('Nama Lokasi')
                                    ->placeholder('Tidak ada nama lokasi'),
                                Infolists\Components\TextEntry::make('accuracy')
                                    ->label('Akurasi GPS')
                                    ->suffix(' meter')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('latitude')
                                    ->label('Latitude')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('longitude')
                                    ->label('Longitude')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->latitude && $record->longitude),

                Infolists\Components\Section::make('Lokasi Check-out')
                    ->schema([
                        MapEntry::make('checkout_map')
                            ->label('')
                            ->columnSpanFull()
                            ->height('300px')
                            ->zoom(15)
                            ->mapType('roadmap')
                            ->showMarker()
                            ->markerColor('green')
                            ->formatStateUsing(function ($record) {
                                if ($record->checkout_latitude && $record->checkout_longitude) {
                                    return [
                                        'lat' => (float) $record->checkout_latitude,
                                        'lng' => (float) $record->checkout_longitude,
                                    ];
                                }
                                return null;
                            }),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('location_name_out')
                                    ->label('Nama Lokasi')
                                    ->placeholder('Tidak ada nama lokasi'),
                                Infolists\Components\TextEntry::make('checkout_accuracy')
                                    ->label('Akurasi GPS')
                                    ->suffix(' meter')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('checkout_latitude')
                                    ->label('Latitude')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('checkout_longitude')
                                    ->label('Longitude')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->checkout_latitude && $record->checkout_longitude),

                Infolists\Components\Section::make('Foto & Catatan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ImageEntry::make('photo_in')
                                    ->label('Foto Check-in')
                                    ->height(250)
                                    ->placeholder('Tidak ada foto'),
                                Infolists\Components\ImageEntry::make('photo_out')
                                    ->label('Foto Check-out')
                                    ->height(250)
                                    ->placeholder('Tidak ada foto'),
                            ]),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan')
                            ->prose(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('location_validated')
                                    ->label('Status Validasi')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Tervalidasi' : 'Belum Divalidasi')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                                Infolists\Components\TextEntry::make('device_id')
                                    ->label('ID Device')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime('d/m/Y H:i:s'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Diperbarui')
                                    ->dateTime('d/m/Y H:i:s'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function getTitle(): string
    {
        $date = $this->record->date->format('d/m/Y');
        return "Detail Presensi - {$date}";
    }
}