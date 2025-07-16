<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeePerformanceResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Employee Performance';
    
    protected static ?string $navigationGroup = '👥 Personnel Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Pegawai')
                    ->required(),
                    
                Forms\Components\TextInput::make('nik')
                    ->label('NIK')
                    ->required(),
                    
                Forms\Components\Select::make('jabatan')
                    ->label('Jabatan')
                    ->options([
                        'dokter' => 'Dokter',
                        'perawat' => 'Perawat',
                        'administrasi' => 'Administrasi',
                        'farmasi' => 'Farmasi',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('performance_score')
                    ->label('Performance Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->badge()
                    ->colors([
                        'primary' => 'dokter',
                        'success' => 'perawat',
                        'warning' => 'administrasi',
                        'info' => 'farmasi',
                    ]),
                    
                Tables\Columns\TextColumn::make('user.attendances_count')
                    ->label('Kehadiran')
                    ->counts('user.attendances')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('performance_score')
                    ->label('Performance')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jabatan')
                    ->label('Jabatan')
                    ->options([
                        'dokter' => 'Dokter',
                        'perawat' => 'Perawat',
                        'administrasi' => 'Administrasi',
                        'farmasi' => 'Farmasi',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('performance_score', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\EmployeePerformanceResource\Pages\ListEmployeePerformances::route('/'),
        ];
    }
}