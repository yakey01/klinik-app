<?php

namespace App\Filament\Bendahara\Resources;

use App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource\Pages;
use App\Models\JumlahPasienHarian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ValidasiJumlahPasienResource extends Resource
{
    protected static ?string $model = JumlahPasienHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    
    protected static ?string $navigationLabel = 'Validasi Pasien Harian';
    
    protected static ?string $modelLabel = 'Validasi Jumlah Pasien';
    
    protected static ?string $pluralModelLabel = 'Validasi Jumlah Pasien';
    
    protected static ?string $navigationGroup = 'Validasi Data';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Data Pasien')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->disabled()
                                    ->displayFormat('d/m/Y'),
                                    
                                Forms\Components\Select::make('poli')
                                    ->label('Poli')
                                    ->options([
                                        'umum' => 'Poli Umum',
                                        'gigi' => 'Poli Gigi',
                                    ])
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_pasien_umum')
                                    ->label('Pasien Umum')
                                    ->numeric()
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('jumlah_pasien_bpjs')
                                    ->label('Pasien BPJS')
                                    ->numeric()
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('total_pasien')
                                    ->label('Total Pasien')
                                    ->numeric()
                                    ->disabled()
                                    ->default(fn ($record) => $record ? $record->total_pasien : 0),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('dokter_id')
                                    ->label('Dokter Pelaksana')
                                    ->relationship('dokter', 'nama_lengkap')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('inputBy.name')
                                    ->label('Diinput Oleh')
                                    ->disabled(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Validasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                    ])
                                    ->required()
                                    ->default('pending'),
                                    
                                Forms\Components\TextInput::make('validasiBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn ($record) => $record && $record->validasi_by),
                            ]),
                            
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->rows(3)
                            ->placeholder('Tambahkan catatan jika diperlukan...'),
                            
                        Forms\Components\DateTimePicker::make('validasi_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->validasi_at),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditValidasiJumlahPasien),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('poli')
                    ->label('Poli')
                    ->colors([
                        'primary' => 'umum',
                        'success' => 'gigi',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('jumlah_pasien_umum')
                    ->label('Pasien Umum')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('jumlah_pasien_bpjs')
                    ->label('Pasien BPJS')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('total_pasien')
                    ->label('Total')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),
                    
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Divalidasi Oleh')
                    ->placeholder('Belum divalidasi'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending'),
                    
                Tables\Filters\SelectFilter::make('poli')
                    ->label('Poli')
                    ->options([
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                    ]),
                    
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Persetujuan')
                            ->placeholder('Tambahkan catatan jika diperlukan...')
                            ->rows(3),
                    ])
                    ->action(function (JumlahPasienHarian $record, array $data): void {
                        $record->approve(auth()->user(), $data['catatan_validasi'] ?? null);
                        
                        // Trigger jaspel calculation here
                        \App\Jobs\HitungJaspelPasienJob::dispatch($record->id);
                    })
                    ->visible(fn (JumlahPasienHarian $record): bool => $record->isPending()),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Alasan Penolakan')
                            ->placeholder('Jelaskan alasan penolakan...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (JumlahPasienHarian $record, array $data): void {
                        $record->reject(auth()->user(), $data['catatan_validasi']);
                    })
                    ->visible(fn (JumlahPasienHarian $record): bool => $record->isPending()),
                    
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_bulk')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->approve(auth()->user());
                                    \App\Jobs\HitungJaspelPasienJob::dispatch($record->id);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
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
            'index' => Pages\ListValidasiJumlahPasiens::route('/'),
            'view' => Pages\ViewValidasiJumlahPasien::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['dokter', 'inputBy', 'validasiBy']);
    }
}
