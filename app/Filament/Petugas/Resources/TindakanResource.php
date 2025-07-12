<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\TindakanResource\Pages;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';
    
    protected static ?string $navigationGroup = 'Input Data';
    
    protected static ?string $modelLabel = 'Tindakan';
    
    protected static ?string $pluralModelLabel = 'Input Tindakan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('jenis_tindakan_id')
                            ->label('Jenis Tindakan')
                            ->required()
                            ->options(JenisTindakan::active()->pluck('nama', 'id'))
                            ->searchable()
                            ->placeholder('Pilih jenis tindakan')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $jenisTindakan = JenisTindakan::find($state);
                                    if ($jenisTindakan) {
                                        $set('tarif', $jenisTindakan->tarif);
                                        $set('jasa_dokter', $jenisTindakan->jasa_dokter);
                                        $set('jasa_paramedis', $jenisTindakan->jasa_paramedis);
                                        $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                    }
                                }
                            }),
                        
                        Forms\Components\Select::make('pasien_id')
                            ->label('Pasien')
                            ->required()
                            ->options(Pasien::orderBy('nama')->get()->mapWithKeys(fn (Pasien $pasien) => [$pasien->id => "{$pasien->no_rekam_medis} - {$pasien->nama}"]))
                            ->searchable()
                            ->placeholder('Pilih pasien'),
                        
                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->required()
                            ->default(now())
                            ->maxDateTime(now()),
                        
                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('100000')
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('Jasa Dokter (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('Jasa Paramedis (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),
                        
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->maxLength(500)
                            ->placeholder('Catatan tindakan (opsional)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(30)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),
                
                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_tindakan')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),
                
                Tables\Filters\SelectFilter::make('jenis_tindakan_id')
                    ->label('Jenis Tindakan')
                    ->options(JenisTindakan::active()->pluck('nama', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Tindakan $record): bool => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Tindakan $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete_any_tindakan')),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', Auth::id())
            ->with(['jenisTindakan', 'pasien']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTindakans::route('/'),
            'create' => Pages\CreateTindakan::route('/create'),
            'view' => Pages\ViewTindakan::route('/{record}'),
            'edit' => Pages\EditTindakan::route('/{record}/edit'),
        ];
    }
}