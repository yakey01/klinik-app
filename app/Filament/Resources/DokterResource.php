<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokterResource\Pages;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DokterResource extends Resource
{
    protected static ?string $model = Dokter::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'SDM';
    protected static ?string $navigationLabel = 'Manajemen Dokter';
    protected static ?int $navigationSort = 23;
    protected static ?string $modelLabel = 'Dokter';
    protected static ?string $pluralModelLabel = 'Dokter';
    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👨‍⚕️ Informasi Dokter')
                    ->description('Data pribadi dan identitas dokter')
                    ->schema([
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Dr. Ahmad Yusuf Sp.PD')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Pegawai')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generate jika kosong')
                            ->helperText('Format: DOK2025XXXX')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(true)
                            ->maxDate(now()->subYears(22)) // Min umur 22 tahun untuk dokter
                            ->minDate(now()->subYears(80)) // Max umur 80 tahun
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Select::make('jabatan')
                            ->label('Jabatan / Spesialisasi')
                            ->options([
                                'dokter_umum' => 'Dokter Umum',
                                'dokter_gigi' => 'Dokter Gigi',
                                'dokter_spesialis' => 'Dokter Spesialis',
                            ])
                            ->required()
                            ->default('dokter_umum')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📋 Izin Praktik')
                    ->description('Nomor SIP dan kontak')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_sip')
                            ->label('Nomor SIP')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('50/SIP/XXXX/2024')
                            ->helperText('Nomor Surat Izin Praktik wajib untuk keabsahan')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->placeholder('dokter@klinik.com')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Dokter aktif dapat login dan melakukan tindakan')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📝 Informasi Tambahan')
                    ->description('Catatan dan foto dokter')
                    ->schema([
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto Dokter')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->directory('dokter-photos')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Catatan tambahan tentang dokter...')
                            ->rows(4)
                            ->columnSpan(2),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header: Photo + Name + NIK
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('foto')
                            ->circular()
                            ->size(50)
                            ->defaultImageUrl(fn ($record) => $record->default_avatar),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('nama_lengkap')
                                ->weight(FontWeight::Bold)
                                ->size('sm')
                                ->limit(20)
                                ->tooltip(fn ($record) => $record->nama_lengkap),
                            Tables\Columns\TextColumn::make('nik')
                                ->color('gray')
                                ->size('xs')
                                ->prefix('NIK: '),
                        ])->space(1),
                    ]),
                    
                    // Body: Jabatan + SIP
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('jabatan_display')
                            ->getStateUsing(fn ($record) => $record->jabatan_display)
                            ->badge()
                            ->color(fn ($record) => $record->jabatan_badge_color)
                            ->size('xs'),
                        
                        Tables\Columns\TextColumn::make('nomor_sip')
                            ->prefix('SIP: ')
                            ->color('info')
                            ->size('xs')
                            ->limit(15)
                            ->tooltip(fn ($record) => 'SIP: ' . $record->nomor_sip),
                    ])->space(1),
                    
                    // Footer: Status + Contact
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('status_text')
                            ->getStateUsing(fn ($record) => $record->status_text)
                            ->badge()
                            ->color(fn ($record) => $record->status_badge_color)
                            ->size('xs'),
                        
                        Tables\Columns\TextColumn::make('email')
                            ->icon('heroicon-m-envelope')
                            ->color('gray')
                            ->size('xs')
                            ->limit(20)
                            ->tooltip(fn ($record) => $record->email ?: 'Tidak ada email'),
                    ]),
                ])->space(2),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jabatan')
                    ->label('Jabatan')
                    ->options([
                        'dokter_umum' => 'Dokter Umum',
                        'dokter_gigi' => 'Dokter Gigi',
                        'dokter_spesialis' => 'Dokter Spesialis',
                    ])
                    ->placeholder('Semua Jabatan'),

                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Dokter'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus Dokter'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->striped()
            ->emptyStateHeading('👨‍⚕️ Belum Ada Dokter')
            ->emptyStateDescription('Klik tombol "Tambah Dokter" untuk menambahkan dokter pertama.')
            ->emptyStateIcon('heroicon-o-user-plus');
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
            'index' => Pages\ListDokters::route('/'),
            'create' => Pages\CreateDokter::route('/create'),
            'edit' => Pages\EditDokter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}