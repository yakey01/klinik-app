<?php

namespace App\Filament\Resources\LocationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    
    protected static ?string $title = 'Pengguna';
    
    protected static ?string $modelLabel = 'Pengguna';
    
    protected static ?string $pluralModelLabel = 'Pengguna';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Jabatan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Dokter' => 'warning',
                        'Paramedis' => 'success',
                        'Petugas' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Jabatan')
                    ->relationship('role', 'name'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign Pengguna')
                    ->modalHeading('Assign Pengguna ke Lokasi')
                    ->modalSubmitActionLabel('Assign')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereNull('location_id'))
                    ->recordTitle(fn ($record) => "{$record->name} ({$record->role->name})"),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Lepas')
                    ->requiresConfirmation()
                    ->modalHeading('Lepas Pengguna dari Lokasi')
                    ->modalDescription('Apakah Anda yakin ingin melepas pengguna ini dari lokasi?')
                    ->modalSubmitActionLabel('Ya, Lepas'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Lepas Pengguna')
                        ->requiresConfirmation()
                        ->modalHeading('Lepas Pengguna dari Lokasi')
                        ->modalDescription('Apakah Anda yakin ingin melepas pengguna yang dipilih dari lokasi?')
                        ->modalSubmitActionLabel('Ya, Lepas'),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Pengguna')
            ->emptyStateDescription('Assign pengguna ke lokasi ini untuk memulai.')
            ->emptyStateIcon('heroicon-o-users');
    }
}