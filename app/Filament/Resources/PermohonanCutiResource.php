<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermohonanCutiResource\Pages;
use App\Filament\Resources\PermohonanCutiResource\RelationManagers;
use App\Models\PermohonanCuti;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermohonanCutiResource extends Resource
{
    protected static ?string $model = PermohonanCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    
    protected static ?string $navigationLabel = 'Permohonan Cuti';
    
    protected static ?string $modelLabel = 'Permohonan Cuti';
    
    protected static ?string $pluralModelLabel = 'Permohonan Cuti';
    
    protected static ?string $navigationGroup = 'Kalender & Cuti';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pemohon')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('Pegawai')
                            ->relationship('pegawai', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} - {$record->email}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),
                    ]),
                    
                Forms\Components\Section::make('Detail Cuti')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now()->addDay())
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('tanggal_selesai', null)),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (callable $get) => $get('tanggal_mulai') ? $get('tanggal_mulai') : now()->addDay())
                            ->reactive(),
                        Forms\Components\Select::make('jenis_cuti')
                            ->label('Jenis Cuti')
                            ->options([
                                'Cuti Tahunan' => 'Cuti Tahunan',
                                'Sakit' => 'Sakit',
                                'Izin' => 'Izin',
                                'Dinas Luar' => 'Dinas Luar'
                            ])
                            ->required()
                            ->native(false),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Keterangan')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan/Keterangan')
                            ->placeholder('Jelaskan alasan pengajuan cuti...')
                            ->required()
                            ->rows(4),
                    ]),
                    
                Forms\Components\Section::make('Status Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Menunggu' => 'Menunggu',
                                'Disetujui' => 'Disetujui',
                                'Ditolak' => 'Ditolak'
                            ])
                            ->default('Menunggu')
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->required(),
                        Forms\Components\Select::make('disetujui_oleh')
                            ->label('Disetujui Oleh')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Textarea::make('catatan_approval')
                            ->label('Catatan Approval')
                            ->placeholder('Catatan dari atasan...')
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->rows(3),
                    ])->columns(2)
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Pemohon')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasicuti')
                    ->label('Durasi')
                    ->suffix(' hari')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cuti Tahunan' => 'success',
                        'Sakit' => 'danger',
                        'Izin' => 'warning',
                        'Dinas Luar' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Menunggu' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('Belum ada')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (PermohonanCuti $record, array $data): void {
                        $record->approve(auth()->id(), $data['catatan'] ?? null);
                    })
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Approval')
                            ->placeholder('Catatan untuk pemohon...')
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (PermohonanCuti $record): bool => $record->status === 'Menunggu'),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (PermohonanCuti $record, array $data): void {
                        $record->reject(auth()->id(), $data['catatan']);
                    })
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Penolakan')
                            ->placeholder('Jelaskan alasan penolakan...')
                            ->required()
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (PermohonanCuti $record): bool => $record->status === 'Menunggu'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPermohonanCutis::route('/'),
            'create' => Pages\CreatePermohonanCuti::route('/create'),
            'edit' => Pages\EditPermohonanCuti::route('/{record}/edit'),
        ];
    }
}
