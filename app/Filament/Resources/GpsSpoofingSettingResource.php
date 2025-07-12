<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GpsSpoofingSettingResource\Pages;
use App\Filament\Resources\GpsSpoofingSettingResource\RelationManagers;
use App\Models\GpsSpoofingSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GpsSpoofingSettingResource extends Resource
{
    protected static ?string $model = GpsSpoofingSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?string $navigationLabel = '⚙️ GPS Spoofing Settings';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Pengaturan GPS Spoofing';
    protected static ?string $pluralModelLabel = 'Pengaturan GPS Spoofing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🛡️ Konfigurasi Umum')
                    ->description('Pengaturan dasar sistem deteksi GPS spoofing')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('🔘 Aktifkan GPS Anti-Spoofing')
                            ->helperText('Nonaktifkan untuk debugging atau maintenance')
                            ->default(true)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('📝 Nama Konfigurasi')
                            ->default('GPS Anti-Spoofing Configuration')
                            ->required()
                            ->columnSpan(1),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('📋 Deskripsi')
                            ->default('Konfigurasi sistem deteksi GPS spoofing untuk keamanan presensi')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('🎯 Skor Deteksi per Metode')
                    ->description('Tentukan bobot skor (0-100) untuk setiap metode deteksi')
                    ->schema([
                        Forms\Components\TextInput::make('mock_location_score')
                            ->label('📍 Mock Location')
                            ->helperText('Skor jika mock location terdeteksi aktif')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(25)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('fake_gps_app_score')
                            ->label('📱 Fake GPS App')
                            ->helperText('Skor jika aplikasi GPS palsu terinstall')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(30)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('developer_mode_score')
                            ->label('⚙️ Developer Mode')
                            ->helperText('Skor jika developer mode aktif')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(20)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('impossible_travel_score')
                            ->label('🚀 Impossible Travel')
                            ->helperText('Skor jika terdeteksi perpindahan tidak wajar')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(35)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('coordinate_anomaly_score')
                            ->label('📊 Coordinate Anomaly')
                            ->helperText('Skor jika koordinat tidak wajar (0,0 atau terlalu akurat)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(15)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('device_integrity_score')
                            ->label('🛡️ Device Integrity')
                            ->helperText('Skor jika integritas perangkat bermasalah')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(25)
                            ->suffix('%'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('⚠️ Threshold Level Risiko')
                    ->description('Tentukan batas skor untuk klasifikasi tingkat risiko')
                    ->schema([
                        Forms\Components\TextInput::make('low_risk_threshold')
                            ->label('🟢 Low Risk (0 - X)')
                            ->helperText('Skor di bawah nilai ini = Low Risk')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(30)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('medium_risk_threshold')
                            ->label('🟡 Medium Risk (X - Y)')
                            ->helperText('Skor di atas Low tapi di bawah nilai ini = Medium Risk')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(60)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('high_risk_threshold')
                            ->label('🟠 High Risk (Y - Z)')
                            ->helperText('Skor di atas Medium tapi di bawah nilai ini = High Risk')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(80)
                            ->suffix('%'),
                        
                        Forms\Components\Placeholder::make('critical_info')
                            ->label('🔴 Critical Risk')
                            ->content('Skor ≥ High Risk Threshold = Critical Risk')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('🚨 Threshold Tindakan')
                    ->description('Tentukan kapan sistem harus mengambil tindakan otomatis')
                    ->schema([
                        Forms\Components\TextInput::make('warning_threshold')
                            ->label('⚠️ Warning Threshold')
                            ->helperText('Kirim peringatan jika skor ≥ nilai ini')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('flagged_threshold')
                            ->label('🏳️ Flagged Threshold')
                            ->helperText('Flag untuk review manual jika skor ≥ nilai ini')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(60)
                            ->suffix('%'),
                        
                        Forms\Components\TextInput::make('blocked_threshold')
                            ->label('🚫 Blocked Threshold')
                            ->helperText('Blokir presensi otomatis jika skor ≥ nilai ini')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(80)
                            ->suffix('%'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('🔍 Metode Deteksi Aktif')
                    ->description('Pilih metode deteksi yang akan dijalankan sistem')
                    ->schema([
                        Forms\Components\Toggle::make('detect_mock_location')
                            ->label('📍 Mock Location Detection')
                            ->helperText('Deteksi jika mock location aktif di pengaturan developer')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('detect_fake_gps_apps')
                            ->label('📱 Fake GPS Apps Detection')
                            ->helperText('Deteksi aplikasi GPS palsu yang terinstall')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('detect_developer_mode')
                            ->label('⚙️ Developer Mode Detection')
                            ->helperText('Deteksi jika developer options aktif')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('detect_impossible_travel')
                            ->label('🚀 Impossible Travel Detection')
                            ->helperText('Deteksi perpindahan dengan kecepatan tidak wajar')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('detect_coordinate_anomaly')
                            ->label('📊 Coordinate Anomaly Detection')
                            ->helperText('Deteksi koordinat tidak wajar (0,0 atau akurasi mencurigakan)')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('detect_device_integrity')
                            ->label('🛡️ Device Integrity Detection')
                            ->helperText('Deteksi masalah integritas perangkat (root, emulator)')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('🏃‍♂️ Analisis Pergerakan')
                    ->description('Parameter untuk mendeteksi pergerakan tidak wajar')
                    ->schema([
                        Forms\Components\TextInput::make('max_travel_speed_kmh')
                            ->label('🚗 Kecepatan Maksimal (km/h)')
                            ->helperText('Kecepatan pergerakan maksimal yang realistis')
                            ->numeric()
                            ->step(0.01)
                            ->default(120.00)
                            ->suffix('km/h'),
                        
                        Forms\Components\TextInput::make('min_time_between_locations')
                            ->label('⏱️ Minimal Waktu Antar Lokasi (detik)')
                            ->helperText('Waktu minimum antar presensi untuk analisis pergerakan')
                            ->numeric()
                            ->default(30)
                            ->suffix('detik'),
                        
                        Forms\Components\TextInput::make('accuracy_threshold')
                            ->label('🎯 Threshold Akurasi Mencurigakan (meter)')
                            ->helperText('Akurasi GPS yang terlalu sempurna (mencurigakan)')
                            ->numeric()
                            ->step(0.1)
                            ->default(1.0)
                            ->suffix('meter'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('📧 Pengaturan Notifikasi')
                    ->description('Konfigurasi alert dan notifikasi untuk admin')
                    ->schema([
                        Forms\Components\Toggle::make('send_email_alerts')
                            ->label('📧 Kirim Email Alert')
                            ->helperText('Kirim notifikasi email ke admin')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('send_realtime_alerts')
                            ->label('🔔 Real-time Alert')
                            ->helperText('Tampilkan notifikasi real-time di dashboard')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('send_critical_only')
                            ->label('🚨 Hanya Critical Alert')
                            ->helperText('Hanya kirim notifikasi untuk tingkat Critical')
                            ->default(false),
                        
                        Forms\Components\TagsInput::make('notification_recipients')
                            ->label('📬 Email Penerima Notifikasi')
                            ->helperText('Daftar email admin yang akan menerima alert')
                            ->placeholder('admin@dokterku.com')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('🚫 Pengaturan Blocking')
                    ->description('Konfigurasi pemblokiran otomatis')
                    ->schema([
                        Forms\Components\Toggle::make('auto_block_enabled')
                            ->label('🤖 Auto Block Aktif')
                            ->helperText('Blokir presensi secara otomatis jika skor tinggi')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('block_duration_hours')
                            ->label('⏰ Durasi Block (jam)')
                            ->helperText('Lama pemblokiran otomatis')
                            ->numeric()
                            ->default(24)
                            ->suffix('jam'),
                        
                        Forms\Components\Toggle::make('require_admin_unblock')
                            ->label('👨‍💼 Perlu Admin Unblock')
                            ->helperText('User tidak bisa unblock sendiri, harus admin')
                            ->default(true),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('✅ Whitelist & Trusted')
                    ->description('Pengecualian untuk IP, device, dan lokasi terpercaya')
                    ->schema([
                        Forms\Components\TagsInput::make('whitelisted_ips')
                            ->label('🌐 IP Address Whitelist')
                            ->helperText('IP yang dikecualikan dari deteksi')
                            ->placeholder('192.168.1.100'),
                        
                        Forms\Components\TagsInput::make('whitelisted_devices')
                            ->label('📱 Device ID Whitelist')
                            ->helperText('Device yang dikecualikan dari deteksi')
                            ->placeholder('device-unique-id'),
                        
                        Forms\Components\Textarea::make('trusted_locations')
                            ->label('📍 Trusted Locations (JSON)')
                            ->helperText('Lokasi terpercaya dalam format JSON array')
                            ->placeholder('[{"name":"Kantor Pusat","latitude":-6.2088,"longitude":106.8238,"radius":100}]')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('📱 Database Fake GPS Apps')
                    ->description('Daftar aplikasi GPS palsu yang dideteksi')
                    ->schema([
                        Forms\Components\Textarea::make('fake_gps_apps_database')
                            ->label('🚫 Fake GPS Apps Package Names')
                            ->helperText('Daftar package name aplikasi GPS palsu (JSON array)')
                            ->placeholder('["com.lexa.fakegps", "com.incorporateapps.fakegps"]')
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('📊 Pengaturan Logging')
                    ->description('Konfigurasi penyimpanan log deteksi')
                    ->schema([
                        Forms\Components\Toggle::make('log_all_attempts')
                            ->label('📝 Log Semua Percobaan')
                            ->helperText('Simpan semua percobaan presensi, termasuk yang bersih')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('log_low_risk_only')
                            ->label('🟢 Hanya Log Low Risk+')
                            ->helperText('Jika aktif, hanya log yang memiliki risiko (tidak log yang 0%)')
                            ->default(false),
                        
                        Forms\Components\TextInput::make('retention_days')
                            ->label('📅 Retensi Log (hari)')
                            ->helperText('Berapa lama log disimpan sebelum dihapus otomatis')
                            ->numeric()
                            ->default(90)
                            ->suffix('hari'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('📝 Nama Konfigurasi')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('🔘 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('blocked_threshold')
                    ->label('🚫 Block Threshold')
                    ->suffix('%')
                    ->badge()
                    ->color('danger'),
                
                Tables\Columns\TextColumn::make('detection_methods_count')
                    ->label('🔍 Metode Aktif')
                    ->getStateUsing(function ($record) {
                        return count($record->getEnabledMethods()) . '/6';
                    })
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\IconColumn::make('auto_block_enabled')
                    ->label('🤖 Auto Block')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('🕐 Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updater.name')
                    ->label('👤 Diupdate Oleh')
                    ->default('System')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                
                Tables\Filters\TernaryFilter::make('auto_block_enabled')
                    ->label('Auto Block')
                    ->placeholder('Semua')
                    ->trueLabel('Auto Block ON')
                    ->falseLabel('Auto Block OFF'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('⚙️ Edit')
                    ->color('primary'),
                
                Tables\Actions\Action::make('test_settings')
                    ->label('🧪 Test')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->action(function ($record) {
                        // Test settings dengan sample data
                        $testData = [
                            'latitude' => -6.2088,
                            'longitude' => 106.8238,
                            'mock_location_enabled' => true,
                            'fake_gps_apps' => ['com.lexa.fakegps'],
                        ];
                        
                        $service = app(\App\Services\GpsSpoofingDetectionService::class);
                        $result = $service->analyzeGpsData(auth()->user(), $testData);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('🧪 Test Results')
                            ->body("Risk Score: {$result['risk_score']}% | Level: {$result['risk_level']} | Action: {$result['action']}")
                            ->color($result['risk_level'] === 'low' ? 'success' : 'danger')
                            ->send();
                    }),
                
                Tables\Actions\Action::make('view_summary')
                    ->label('📊 Summary')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading('📊 Configuration Summary')
                    ->modalContent(function ($record) {
                        $summary = $record->getSummary();
                        $html = '<div class="space-y-3">';
                        foreach ($summary as $key => $value) {
                            $html .= '<div class="flex justify-between"><span class="font-medium">' . ucfirst(str_replace('_', ' ', $key)) . ':</span><span>' . $value . '</span></div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    }),
            ])
            ->bulkActions([
                // Remove bulk actions for settings (should only have one record)
            ])
            ->emptyStateHeading('⚙️ Belum Ada Konfigurasi')
            ->emptyStateDescription('Klik tombol "New" untuk membuat konfigurasi GPS anti-spoofing pertama.')
            ->emptyStateIcon('heroicon-o-cog-8-tooth');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageGpsSpoofingSettings::route('/'),
        ];
    }
}
