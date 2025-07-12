<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PasienResource\Pages;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Input Data';
    
    protected static ?string $modelLabel = 'Pasien';
    
    protected static ?string $pluralModelLabel = 'Input Pasien';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('no_rekam_medis')
                            ->label('No. Rekam Medis')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Contoh: RM-2024-001')
                            ->helperText('Nomor rekam medis akan di-generate otomatis jika dikosongkan')
                            ->default(fn () => 'RM-' . date('Y') . '-' . str_pad(Pasien::count() + 1, 3, '0', STR_PAD_LEFT)),
                        
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap pasien'),
                        
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->maxDate(now())
                            ->placeholder('Pilih tanggal lahir'),
                        
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->placeholder('Pilih jenis kelamin'),
                        
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->maxLength(500)
                            ->placeholder('Masukkan alamat lengkap')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Contoh: 08123456789'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('Contoh: pasien@email.com'),
                        
                        Forms\Components\TextInput::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->maxLength(100)
                            ->placeholder('Contoh: Karyawan swasta'),
                        
                        Forms\Components\Select::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->options([
                                'belum_menikah' => 'Belum Menikah',
                                'menikah' => 'Menikah',
                                'janda' => 'Janda',
                                'duda' => 'Duda',
                            ])
                            ->placeholder('Pilih status pernikahan'),
                        
                        Forms\Components\TextInput::make('kontak_darurat_nama')
                            ->label('Nama Kontak Darurat')
                            ->maxLength(255)
                            ->placeholder('Nama keluarga/kerabat'),
                        
                        Forms\Components\TextInput::make('kontak_darurat_telepon')
                            ->label('No. Telepon Kontak Darurat')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Contoh: 08123456789'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_rekam_medis')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tgl. Lahir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Pasien $record): string => $record->umur ? $record->umur . ' tahun' : ''),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                
                Tables\Filters\Filter::make('tanggal_lahir')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Lahir Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Lahir Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('status_pernikahan')
                    ->label('Status Pernikahan')
                    ->options([
                        'belum_menikah' => 'Belum Menikah',
                        'menikah' => 'Menikah',
                        'janda' => 'Janda',
                        'duda' => 'Duda',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete_any_pasien')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'create' => Pages\CreatePasien::route('/create'),
            'view' => Pages\ViewPasien::route('/{record}'),
            'edit' => Pages\EditPasien::route('/{record}/edit'),
        ];
    }
}