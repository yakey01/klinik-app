<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class ValidasiPendapatanResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Validasi Pendapatan';
    
    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_pendapatan')
                    ->label('Nama Pendapatan')
                    ->required(),
                    
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required(),
                    
                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('status_validasi')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
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
                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Nama Pendapatan')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status_validasi')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('setujui')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Pendapatan $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'disetujui',
                                    'validasi_by' => auth()->id(),
                                    'validasi_at' => now(),
                                ]);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Pendapatan disetujui')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Pendapatan $record) => $record->status_validasi === 'pending'),
                        
                    Action::make('tolak')
                        ->label('❌ Tolak')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Pendapatan $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'ditolak',
                                    'validasi_by' => auth()->id(),
                                    'validasi_at' => now(),
                                ]);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Pendapatan ditolak')
                                    ->warning()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Pendapatan $record) => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()->label('✏️ Edit'),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->defaultSort('tanggal', 'desc');
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
            'index' => \App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages\ListValidasiPendapatan::route('/'),
        ];
    }
}