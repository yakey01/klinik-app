<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkLocationResource\Pages;
use App\Filament\Resources\WorkLocationResource\RelationManagers;
use App\Models\WorkLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dotswan\MapPicker\Fields\Map;

class WorkLocationResource extends Resource
{
    protected static ?string $model = WorkLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationLabel = 'Validasi Lokasi (Geofencing)';
    
    protected static ?string $modelLabel = 'Lokasi Kerja';
    
    protected static ?string $pluralModelLabel = 'Lokasi Kerja';
    
    protected static ?string $navigationGroup = 'Presensi';
    
    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏢 Informasi Lokasi')
                    ->description('Konfigurasi dasar lokasi kerja')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Lokasi')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Kantor Pusat Jakarta'),

                                Forms\Components\Select::make('location_type')
                                    ->label('Jenis Lokasi')
                                    ->required()
                                    ->options([
                                        'main_office' => '🏢 Kantor Pusat',
                                        'branch_office' => '🏪 Kantor Cabang',
                                        'project_site' => '🚧 Lokasi Proyek',
                                        'mobile_location' => '📱 Lokasi Mobile',
                                        'client_office' => '🤝 Kantor Klien',
                                    ])
                                    ->default('main_office')
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi detail lokasi kerja...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->required()
                            ->placeholder('Masukkan alamat lengkap lokasi...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('📍 Koordinat GPS & Geofencing')
                    ->description('Pilih lokasi pada peta atau gunakan tombol "Get Location" untuk deteksi GPS otomatis')
                    ->schema([
                        Map::make('location')
                            ->label('📍 Pilih Lokasi pada Peta')
                            ->extraStyles(['height: 400px'])
                            ->zoom(15)
                            ->showMyLocationButton()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, ?array $state): void {
                                $set('latitude', $state['lat'] ?? null);
                                $set('longitude', $state['lng'] ?? null);
                            })
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Contoh: -6.2088200 (Jakarta)')
                                    ->helperText('Koordinat lintang - terisi otomatis dari peta')
                                    ->reactive(),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Contoh: 106.8238800 (Jakarta)')
                                    ->helperText('Koordinat bujur - terisi otomatis dari peta')
                                    ->reactive()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('openMaps')
                                            ->label('🗺️ Lihat di Maps')
                                            ->icon('heroicon-o-map')
                                            ->color('success')
                                            ->url(fn ($get) => $get('latitude') && $get('longitude') 
                                                ? "https://maps.google.com/maps?q={$get('latitude')},{$get('longitude')}" 
                                                : 'https://maps.google.com')
                                            ->openUrlInNewTab()
                                    ),

                                Forms\Components\TextInput::make('radius_meters')
                                    ->label('Radius Geofence (meter)')
                                    ->required()
                                    ->numeric()
                                    ->default(100)
                                    ->minValue(10)
                                    ->maxValue(1000)
                                    ->suffix('meter')
                                    ->helperText('Area valid untuk absensi (10-1000m)'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('gps_accuracy_required')
                                    ->label('Akurasi GPS Minimum')
                                    ->required()
                                    ->numeric()
                                    ->default(20)
                                    ->minValue(5)
                                    ->maxValue(100)
                                    ->suffix('meter')
                                    ->helperText('Akurasi GPS minimum yang diperlukan'),

                                Forms\Components\Toggle::make('strict_geofence')
                                    ->label('Geofence Ketat')
                                    ->default(true)
                                    ->helperText('Apakah geofence harus ketat atau fleksibel'),
                            ]),

                        Forms\Components\Placeholder::make('location_tips')
                            ->label('💡 Tips Penggunaan Peta:')
                            ->content('
                                • Klik tombol "🌐 Get Location" untuk deteksi GPS otomatis
                                • Drag marker pada peta untuk mengubah posisi
                                • Gunakan search box untuk mencari alamat
                                • Zoom in/out dengan scroll mouse atau kontrol peta
                                • Koordinat akan terisi otomatis saat memilih lokasi
                            ')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('⚙️ Pengaturan Kerja')
                    ->description('Konfigurasi shift dan jam kerja')
                    ->schema([
                        Forms\Components\CheckboxList::make('allowed_shifts')
                            ->label('Shift yang Diizinkan')
                            ->options([
                                'Pagi' => '🌅 Shift Pagi (08:00-14:00)',
                                'Siang' => '☀️ Shift Siang (14:00-20:00)',
                                'Malam' => '🌙 Shift Malam (20:00-08:00)',
                            ])
                            ->descriptions([
                                'Pagi' => 'Shift pagi untuk operasional normal',
                                'Siang' => 'Shift siang untuk layanan sore',
                                'Malam' => 'Shift malam untuk keamanan/emergency',
                            ])
                            ->columns(3)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('tolerance_settings')
                            ->label('Pengaturan Toleransi (menit)')
                            ->keyLabel('Jenis Toleransi')
                            ->valueLabel('Durasi (menit)')
                            ->default([
                                'late_tolerance_minutes' => 15,
                                'early_departure_tolerance_minutes' => 15,
                                'break_time_minutes' => 60,
                                'overtime_threshold_minutes' => 480,
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('👤 Kontak & Verifikasi')
                    ->description('Penanggung jawab dan pengaturan keamanan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Penanggung Jawab')
                                    ->placeholder('Nama penanggung jawab lokasi'),

                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Nomor Telepon')
                                    ->tel()
                                    ->placeholder('08123456789'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('require_photo')
                                    ->label('Wajib Foto Selfie')
                                    ->default(true)
                                    ->helperText('Karyawan wajib upload foto saat absen'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->helperText('Lokasi dapat digunakan untuk absensi'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('📍 Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('location_type')
                    ->label('Jenis Lokasi')
                    ->badge()
                    ->colors([
                        'primary' => 'main_office',
                        'success' => 'branch_office',
                        'warning' => 'project_site',
                        'info' => 'mobile_location',
                        'secondary' => 'client_office',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'main_office' => '🏢 Kantor Pusat',
                        'branch_office' => '🏪 Kantor Cabang',
                        'project_site' => '🚧 Lokasi Proyek',
                        'mobile_location' => '📱 Lokasi Mobile',
                        'client_office' => '🤝 Kantor Klien',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address)
                    ->searchable(),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('📍 Koordinat')
                    ->formatStateUsing(fn ($record) => 
                        number_format($record->latitude, 6) . ', ' . number_format($record->longitude, 6)
                    )
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_radius')
                    ->label('🎯 Radius')
                    ->color('warning')
                    ->weight('semibold'),

                Tables\Columns\IconColumn::make('require_photo')
                    ->label('📸 Foto')
                    ->boolean()
                    ->trueIcon('heroicon-o-camera')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('strict_geofence')
                    ->label('🛡️ Ketat')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('✅ Aktif')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('👤 Penanggung Jawab')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_type')
                    ->label('Jenis Lokasi')
                    ->options([
                        'main_office' => '🏢 Kantor Pusat',
                        'branch_office' => '🏪 Kantor Cabang',
                        'project_site' => '🚧 Lokasi Proyek',
                        'mobile_location' => '📱 Lokasi Mobile',
                        'client_office' => '🤝 Kantor Klien',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\TernaryFilter::make('require_photo')
                    ->label('Wajib Foto')
                    ->boolean()
                    ->trueLabel('Wajib Foto')
                    ->falseLabel('Tidak Wajib'),

                Tables\Filters\TernaryFilter::make('strict_geofence')
                    ->label('Geofence Ketat')
                    ->boolean()
                    ->trueLabel('Ketat')
                    ->falseLabel('Fleksibel'),

                Tables\Filters\Filter::make('radius_range')
                    ->label('Rentang Radius')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('radius_min')
                                    ->label('Radius Minimum (m)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('radius_max')
                                    ->label('Radius Maximum (m)')
                                    ->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['radius_min'], fn ($q, $min) => $q->where('radius_meters', '>=', $min))
                            ->when($data['radius_max'], fn ($q, $max) => $q->where('radius_meters', '<=', $max));
                    }),
            ])
            ->actions([
                Action::make('view_map')
                    ->label('🗺️ Lihat Peta')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn ($record) => $record->google_maps_url)
                    ->openUrlInNewTab(),

                Action::make('test_geofence')
                    ->label('🎯 Test Geofence')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('test_latitude')
                                    ->label('Test Latitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001),
                                Forms\Components\TextInput::make('test_longitude')
                                    ->label('Test Longitude')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001),
                            ]),
                        Forms\Components\TextInput::make('test_accuracy')
                            ->label('GPS Accuracy (meter)')
                            ->numeric()
                            ->default(10),
                    ])
                    ->action(function ($record, $data) {
                        $isValid = $record->isWithinGeofence(
                            $data['test_latitude'],
                            $data['test_longitude'],
                            $data['test_accuracy'] ?? null
                        );
                        
                        $distance = $record->calculateDistance(
                            $data['test_latitude'],
                            $data['test_longitude']
                        );

                        Notification::make()
                            ->title($isValid ? '✅ Lokasi Valid!' : '❌ Lokasi Tidak Valid!')
                            ->body("Jarak: " . number_format($distance) . "m dari radius {$record->radius_meters}m")
                            ->color($isValid ? 'success' : 'danger')
                            ->duration(5000)
                            ->send();
                    }),

                Action::make('copy_coordinates')
                    ->label('📋 Copy Koordinat')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function ($record) {
                        $coordinates = "{$record->latitude},{$record->longitude}";
                        
                        Notification::make()
                            ->title('📋 Koordinat Disalin!')
                            ->body("Koordinat: {$coordinates}")
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('✅ Aktifkan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title("✅ {$count} lokasi diaktifkan!")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('❌ Nonaktifkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title("❌ {$count} lokasi dinonaktifkan!")
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->poll('60s')
            ->emptyStateHeading('📍 Belum Ada Lokasi Kerja')
            ->emptyStateDescription('Tambahkan lokasi kerja pertama untuk mengaktifkan validasi geofencing.')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Lokasi Pertama')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkLocations::route('/'),
            'create' => Pages\CreateWorkLocation::route('/create'),
            'view' => Pages\ViewWorkLocation::route('/{record}'),
            'edit' => Pages\EditWorkLocation::route('/{record}/edit'),
        ];
    }
}