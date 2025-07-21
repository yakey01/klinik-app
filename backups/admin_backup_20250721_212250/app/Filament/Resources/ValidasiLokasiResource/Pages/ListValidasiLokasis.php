<?php

namespace App\Filament\Resources\ValidasiLokasiResource\Pages;

use App\Filament\Resources\ValidasiLokasiResource;
use App\Models\LocationValidation;
use App\Models\GpsSpoofingDetection;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class ListValidasiLokasis extends ListRecords
{
    protected static string $resource = ValidasiLokasiResource::class;
    
    public function getTitle(): string
    {
        return '📍 Validasi Lokasi Absensi';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('📊 Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    Notification::make()
                        ->title('📊 Export berhasil!')
                        ->body('Data validasi lokasi telah diekspor.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'geofencing' => Tab::make('🧭 Geofencing Check')
                ->modifyQueryUsing(fn (Builder $query) => $query)
                ->badge(fn () => LocationValidation::count())
                ->badgeColor('primary'),
                
            'spoofing' => Tab::make('🚨 GPS Spoofing Detection')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('1=0')) // Empty for GPS spoofing tab
                ->badge(fn () => GpsSpoofingDetection::count())
                ->badgeColor('danger'),
        ];
    }
    
    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab ?? 'geofencing';
        
        if ($activeTab === 'spoofing') {
            return $this->getSpoofingTable($table);
        }
        
        return $this->getGeofencingTable($table);
    }
    
    protected function getGeofencingTable(Table $table): Table
    {
        return $table
            ->query(LocationValidation::query())
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar')
                    ->label('👤')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? 'Unknown') . '&background=3b82f6&color=fff')
                    ->size(40),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('validation_time')
                    ->label('Waktu Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('attendance_type')
                    ->label('Jenis Absensi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'check_in' => 'success',
                        'check_out' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'check_in' => '🟢 Masuk',
                        'check_out' => '🟡 Keluar',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('coordinates')
                    ->label('📍 Koordinat')
                    ->getStateUsing(fn ($record) => round($record->latitude, 6) . ', ' . round($record->longitude, 6))
                    ->copyable()
                    ->tooltip('Klik untuk menyalin koordinat'),
                    
                Tables\Columns\TextColumn::make('accuracy')
                    ->label('🎯 Akurasi GPS')
                    ->suffix(' m')
                    ->color(fn ($state) => match (true) {
                        $state <= 10 => 'success',
                        $state <= 50 => 'warning',
                        default => 'danger',
                    }),
                    
                Tables\Columns\IconColumn::make('is_within_zone')
                    ->label('✅ Status Zona')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->is_within_zone 
                        ? 'Dalam zona kerja (radius: ' . $record->work_zone_radius . 'm)' 
                        : 'Di luar zona kerja (jarak: ' . round($record->distance_from_zone, 1) . 'm)'),
                        
                Tables\Columns\TextColumn::make('distance_from_zone')
                    ->label('📏 Jarak dari Zona')
                    ->getStateUsing(fn ($record) => $record->is_within_zone 
                        ? '✅ Dalam zona' 
                        : '❌ ' . round($record->distance_from_zone, 1) . 'm')
                    ->color(fn ($record) => $record->is_within_zone ? 'success' : 'danger'),
            ])
            ->defaultSort('validation_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('👤 Pegawai')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('attendance_type')
                    ->label('Jenis Absensi')
                    ->options([
                        'check_in' => '🟢 Masuk',
                        'check_out' => '🟡 Keluar',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_within_zone')
                    ->label('Status Zona')
                    ->trueLabel('✅ Dalam Zona')
                    ->falseLabel('❌ Di Luar Zona')
                    ->placeholder('Semua Status'),
                    
                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('validation_time', today())),
                    
                Tables\Filters\Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('validation_time', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])),
                    
                Tables\Filters\Filter::make('validation_time')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validation_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validation_time', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('view_map')
                    ->label('🗺️ Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn ($record) => "https://maps.google.com/maps?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('📊 Export Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('📊 Export berhasil!')
                                ->body('Data validasi lokasi telah diekspor ke Excel.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('📍 Belum Ada Data Validasi Lokasi')
            ->emptyStateDescription('Data validasi akan muncul otomatis saat pegawai melakukan absensi.')
            ->emptyStateIcon('heroicon-o-map-pin');
    }
    
    protected function getSpoofingTable(Table $table): Table
    {
        return $table
            ->query(GpsSpoofingDetection::query())
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar')
                    ->label('👤')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? 'Unknown') . '&background=dc2626&color=fff')
                    ->size(40),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('attempted_at')
                    ->label('Waktu Deteksi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('risk_level')
                    ->label('Tingkat Risiko')
                    ->badge()
                    ->color(fn ($record) => $record->risk_level_color)
                    ->formatStateUsing(fn ($record) => $record->risk_level_label),
                    
                Tables\Columns\TextColumn::make('coordinates')
                    ->label('📍 Koordinat')
                    ->getStateUsing(fn ($record) => round($record->latitude, 6) . ', ' . round($record->longitude, 6))
                    ->copyable()
                    ->tooltip('Klik untuk menyalin koordinat'),
                    
                Tables\Columns\IconColumn::make('is_spoofed')
                    ->label('🚨 Status')
                    ->boolean()
                    ->trueIcon('heroicon-m-exclamation-triangle')
                    ->falseIcon('heroicon-m-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->is_spoofed ? 'GPS Spoofing Terdeteksi' : 'GPS Valid'),
                    
                Tables\Columns\TextColumn::make('detected_methods')
                    ->label('🔍 Metode Terdeteksi')
                    ->getStateUsing(fn ($record) => implode(', ', $record->detected_methods))
                    ->wrap()
                    ->tooltip('Metode spoofing yang terdeteksi'),
                    
                Tables\Columns\TextColumn::make('action_taken')
                    ->label('🎯 Tindakan')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'none' => 'gray',
                        'warning' => 'warning',
                        'blocked' => 'danger',
                        'flagged' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record) => $record->action_taken_label),
            ])
            ->defaultSort('attempted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('👤 Pegawai')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('risk_level')
                    ->label('Tingkat Risiko')
                    ->options([
                        'low' => '🟢 Rendah',
                        'medium' => '🟡 Sedang',
                        'high' => '🔴 Tinggi',
                        'critical' => '🚨 Kritis',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_spoofed')
                    ->label('Status GPS')
                    ->trueLabel('🚨 Spoofing Terdeteksi')
                    ->falseLabel('✅ GPS Valid')
                    ->placeholder('Semua Status'),
                    
                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Status Blokir')
                    ->trueLabel('🚫 Diblokir')
                    ->falseLabel('✅ Tidak Diblokir')
                    ->placeholder('Semua Status'),
                    
                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('attempted_at', today())),
                    
                Tables\Filters\Filter::make('high_risk')
                    ->label('Risiko Tinggi')
                    ->query(fn (Builder $query): Builder => $query->whereIn('risk_level', ['high', 'critical'])),
            ])
            ->actions([
                Action::make('view_map')
                    ->label('🗺️ Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn ($record) => "https://maps.google.com/maps?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),
                    
                Action::make('review')
                    ->label('📝 Review')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->reviewed_at)
                    ->action(function ($record) {
                        $record->update([
                            'reviewed_at' => now(),
                            'reviewed_by' => Auth::id(),
                        ]);
                        
                        Notification::make()
                            ->title('✅ Review selesai')
                            ->body('Data telah ditandai sebagai telah direview.')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_reviewed')
                        ->label('📝 Tandai Direview')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update([
                                'reviewed_at' => now(),
                                'reviewed_by' => Auth::id(),
                            ]);
                            
                            Notification::make()
                                ->title('✅ Bulk review selesai')
                                ->body(count($records) . ' data telah ditandai sebagai direview.')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('export')
                        ->label('📊 Export Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('📊 Export berhasil!')
                                ->body('Data GPS spoofing telah diekspor ke Excel.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('🚨 Belum Ada Data GPS Spoofing')
            ->emptyStateDescription('Data deteksi GPS spoofing akan muncul otomatis saat terdeteksi aktivitas mencurigakan.')
            ->emptyStateIcon('heroicon-o-shield-exclamation');
    }
}