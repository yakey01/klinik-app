<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Lokasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama Lokasi'),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('latitude')
                                    ->label('Latitude'),
                                
                                Infolists\Components\TextEntry::make('longitude')
                                    ->label('Longitude'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('radius')
                            ->label('Radius')
                            ->suffix(' meter'),
                        
                        Infolists\Components\TextEntry::make('users_count')
                            ->label('Jumlah Pengguna')
                            ->state(fn ($record) => $record->users()->count())
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(1),
                
                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d M Y H:i'),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}