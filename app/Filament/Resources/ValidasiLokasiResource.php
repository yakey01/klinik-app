<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidasiLokasiResource\Pages;
use App\Models\LocationValidation;
use App\Models\GpsSpoofingDetection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\MaxWidth;

class ValidasiLokasiResource extends Resource
{
    protected static ?string $model = LocationValidation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationLabel = 'Validasi Lokasi Absensi';
    
    protected static ?string $modelLabel = 'Validasi Lokasi';
    
    protected static ?string $pluralModelLabel = 'Validasi Lokasi';
    
    protected static ?string $navigationGroup = 'Presensi';
    
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('readonly_notice')
                    ->label('📋 Informasi')
                    ->content('Data validasi lokasi ini dibuat secara otomatis oleh sistem. Tidak dapat diedit secara manual.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
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
            'index' => Pages\ListValidasiLokasis::route('/'),
            'create' => Pages\CreateValidasiLokasi::route('/create'),
            'edit' => Pages\EditValidasiLokasi::route('/{record}/edit'),
        ];
    }
}
