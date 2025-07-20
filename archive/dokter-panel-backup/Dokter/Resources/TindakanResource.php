<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\TindakanResource\Pages;
use App\Models\Tindakan;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'My Procedures';
    protected static ?string $modelLabel = 'Tindakan';
    protected static ?string $pluralModelLabel = 'Tindakan Saya';
    protected static ?string $navigationGroup = '🩺 Medical Procedures';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            // Return empty query if doctor not found
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        // Filter tindakan by current doctor
        return parent::getEloquentQuery()
            ->where('dokter_id', $dokter->id)
            ->with(['pasien', 'jenisTindakan', 'dokter', 'paramedis', 'nonParamedis']);
    }

    public static function form(Form $form): Form
    {
        // Doctors can only view, not create/edit procedures
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('pasien.nama_lengkap')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('jenisTindakan.nama_tindakan')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(40),
                    
                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->alignment('right'),
                    
                Tables\Columns\TextColumn::make('jasa_dokter')
                    ->label('Jaspel Dokter')
                    ->money('IDR')
                    ->alignment('right')
                    ->color('success'),
                    
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->icons([
                        'pending' => 'heroicon-o-clock',
                        'disetujui' => 'heroicon-o-check-circle',
                        'ditolak' => 'heroicon-o-x-circle',
                    ]),
                    
                Tables\Columns\TextColumn::make('paramedis.nama_lengkap')
                    ->label('Paramedis')
                    ->limit(20)
                    ->placeholder('Tidak ada'),
                    
                Tables\Columns\TextColumn::make('nonParamedis.nama_lengkap')
                    ->label('Non-Paramedis')
                    ->limit(20)
                    ->placeholder('Tidak ada'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
                    
                Tables\Filters\Filter::make('tanggal_tindakan')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('tanggal_tindakan', now()->month))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for doctors - read only
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->emptyStateHeading('Belum Ada Tindakan')
            ->emptyStateDescription('Anda belum memiliki tindakan yang tercatat dalam sistem.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Tindakan')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->date('d F Y'),
                            
                        Infolists\Components\TextEntry::make('jenisTindakan.nama_tindakan')
                            ->label('Jenis Tindakan'),
                            
                        Infolists\Components\TextEntry::make('tarif')
                            ->label('Tarif')
                            ->money('IDR'),
                            
                        Infolists\Components\TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Informasi Pasien')
                    ->schema([
                        Infolists\Components\TextEntry::make('pasien.nama_lengkap')
                            ->label('Nama Pasien'),
                            
                        Infolists\Components\TextEntry::make('pasien.nomor_rekam_medis')
                            ->label('No. Rekam Medis'),
                            
                        Infolists\Components\TextEntry::make('pasien.tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d F Y'),
                            
                        Infolists\Components\TextEntry::make('pasien.jenis_kelamin')
                            ->label('Jenis Kelamin'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Tim Medis')
                    ->schema([
                        Infolists\Components\TextEntry::make('dokter.nama_lengkap')
                            ->label('Dokter'),
                            
                        Infolists\Components\TextEntry::make('paramedis.nama_lengkap')
                            ->label('Paramedis')
                            ->placeholder('Tidak ada'),
                            
                        Infolists\Components\TextEntry::make('nonParamedis.nama_lengkap')
                            ->label('Non-Paramedis')
                            ->placeholder('Tidak ada'),
                    ])
                    ->columns(3),
                    
                Infolists\Components\Section::make('Finansial & Validasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('jasa_dokter')
                            ->label('Jasa Dokter')
                            ->money('IDR')
                            ->color('success'),
                            
                        Infolists\Components\TextEntry::make('jasa_paramedis')
                            ->label('Jasa Paramedis')
                            ->money('IDR')
                            ->placeholder('Rp 0'),
                            
                        Infolists\Components\TextEntry::make('jasa_non_paramedis')
                            ->label('Jasa Non-Paramedis')
                            ->money('IDR')
                            ->placeholder('Rp 0'),
                            
                        Infolists\Components\TextEntry::make('status_validasi')
                            ->label('Status Validasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                            }),
                            
                        Infolists\Components\TextEntry::make('validated_at')
                            ->label('Divalidasi Pada')
                            ->dateTime('d F Y H:i')
                            ->placeholder('Belum divalidasi'),
                            
                        Infolists\Components\TextEntry::make('komentar_validasi')
                            ->label('Komentar Validasi')
                            ->placeholder('Tidak ada komentar'),
                    ])
                    ->columns(3),
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
            'index' => Pages\ListTindakans::route('/'),
            'view' => Pages\ViewTindakan::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return null;
        }
        
        return static::getModel()::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal_tindakan', now()->month)
            ->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}