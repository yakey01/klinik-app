<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class ValidasiTindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Validasi Pending';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('catatan')
                    ->label('Catatan Tindakan')
                    ->required(),
                    
                Forms\Components\DatePicker::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->required(),
                    
                Forms\Components\TextInput::make('tarif')
                    ->label('Tarif')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status Validasi')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('setujui')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Tindakan $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'approved',
                                    'status' => 'selesai',
                                    'validated_by' => auth()->id(),
                                    'validated_at' => now(),
                                ]);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Tindakan disetujui')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Tindakan $record) => $record->status_validasi === 'pending'),
                        
                    Action::make('tolak')
                        ->label('❌ Tolak')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Tindakan $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'rejected',
                                    'status' => 'batal',
                                    'validated_by' => auth()->id(),
                                    'validated_at' => now(),
                                ]);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Tindakan ditolak')
                                    ->warning()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Tindakan $record) => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()->label('✏️ Edit'),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->defaultSort('tanggal_tindakan', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_validasi', 'pending')
            ->whereNotNull('input_by');
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Always show in navigation
    }
    
    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\ValidasiTindakanResource\Pages\ListValidasiTindakan::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}