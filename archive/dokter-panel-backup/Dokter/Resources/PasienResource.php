<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\PasienResource\Pages;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'My Patients';
    protected static ?string $modelLabel = 'Pasien';
    protected static ?string $pluralModelLabel = 'Pasien Saya';
    protected static ?string $navigationGroup = '👥 Patient Management';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        // Get patients who have been treated by this doctor
        $patientIds = Tindakan::where('dokter_id', $dokter->id)
            ->distinct('pasien_id')
            ->pluck('pasien_id');
        
        return parent::getEloquentQuery()
            ->whereIn('id', $patientIds)
            ->withCount(['tindakan' => function ($query) use ($dokter) {
                $query->where('dokter_id', $dokter->id);
            }]);
    }

    public static function form(Form $form): Form
    {
        // Read-only for doctors
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_rekam_medis')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('age')
                    ->label('Umur')
                    ->state(function (Pasien $record): string {
                        return $record->tanggal_lahir 
                            ? $record->tanggal_lahir->age . ' tahun'
                            : 'Tidak diketahui';
                    })
                    ->alignment('center'),
                    
                Tables\Columns\BadgeColumn::make('jenis_kelamin')
                    ->label('L/P')
                    ->colors([
                        'primary' => 'Laki-laki',
                        'danger' => 'Perempuan',
                    ]),
                    
                Tables\Columns\TextColumn::make('tindakan_count')
                    ->label('Total Tindakan')
                    ->alignment('center')
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('last_visit')
                    ->label('Kunjungan Terakhir')
                    ->state(function (Pasien $record): string {
                        $user = Auth::user();
                        $dokter = Dokter::where('user_id', $user->id)->first();
                        
                        if (!$dokter) return 'Tidak diketahui';
                        
                        $lastTindakan = Tindakan::where('dokter_id', $dokter->id)
                            ->where('pasien_id', $record->id)
                            ->orderBy('tanggal_tindakan', 'desc')
                            ->first();
                            
                        return $lastTindakan 
                            ? $lastTindakan->tanggal_tindakan->format('d M Y')
                            : 'Belum ada';
                    })
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('kontak')
                    ->label('Kontak')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('Tidak ada'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ]),
                    
                Tables\Filters\Filter::make('age_range')
                    ->label('Rentang Umur')
                    ->form([
                        Forms\Components\Select::make('age_category')
                            ->label('Kategori Umur')
                            ->options([
                                'child' => 'Anak (0-17 tahun)',
                                'adult' => 'Dewasa (18-59 tahun)',
                                'elderly' => 'Lansia (60+ tahun)',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['age_category'])) {
                            return $query;
                        }
                        
                        $now = now();
                        return match ($data['age_category']) {
                            'child' => $query->where('tanggal_lahir', '>', $now->subYears(18)),
                            'adult' => $query->whereBetween('tanggal_lahir', [
                                $now->copy()->subYears(60),
                                $now->copy()->subYears(18)
                            ]),
                            'elderly' => $query->where('tanggal_lahir', '<=', $now->subYears(60)),
                            default => $query,
                        };
                    }),
                    
                Tables\Filters\Filter::make('recent_patients')
                    ->label('Pasien Bulan Ini')
                    ->query(function (Builder $query): Builder {
                        $user = Auth::user();
                        $dokter = Dokter::where('user_id', $user->id)->first();
                        
                        if (!$dokter) return $query;
                        
                        $recentPatientIds = Tindakan::where('dokter_id', $dokter->id)
                            ->whereMonth('tanggal_tindakan', now()->month)
                            ->distinct('pasien_id')
                            ->pluck('pasien_id');
                            
                        return $query->whereIn('id', $recentPatientIds);
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('nama_lengkap', 'asc')
            ->emptyStateHeading('Belum Ada Pasien')
            ->emptyStateDescription('Anda belum memiliki pasien yang tercatat dalam sistem.')
            ->emptyStateIcon('heroicon-o-users');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pasien')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_rekam_medis')
                            ->label('No. Rekam Medis'),
                            
                        Infolists\Components\TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap'),
                            
                        Infolists\Components\TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d F Y'),
                            
                        Infolists\Components\TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin'),
                            
                        Infolists\Components\TextEntry::make('kontak')
                            ->label('Kontak')
                            ->placeholder('Tidak ada'),
                            
                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->placeholder('Tidak ada'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Riwayat Tindakan dengan Saya')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('my_tindakan')
                            ->label('')
                            ->state(function (Pasien $record): array {
                                $user = Auth::user();
                                $dokter = Dokter::where('user_id', $user->id)->first();
                                
                                if (!$dokter) return [];
                                
                                return Tindakan::where('dokter_id', $dokter->id)
                                    ->where('pasien_id', $record->id)
                                    ->with(['jenisTindakan'])
                                    ->orderBy('tanggal_tindakan', 'desc')
                                    ->limit(10)
                                    ->get()
                                    ->map(function ($tindakan) {
                                        return [
                                            'tanggal' => $tindakan->tanggal_tindakan->format('d M Y'),
                                            'tindakan' => $tindakan->jenisTindakan?->nama_tindakan ?? 'Tidak diketahui',
                                            'jaspel' => 'Rp ' . number_format($tindakan->jasa_dokter, 0, ',', '.'),
                                            'status' => $tindakan->status_validasi,
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('tanggal')
                                    ->label('Tanggal'),
                                Infolists\Components\TextEntry::make('tindakan')
                                    ->label('Tindakan'),
                                Infolists\Components\TextEntry::make('jaspel')
                                    ->label('Jaspel'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                    }),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'view' => Pages\ViewPasien::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return null;
        }
        
        // Count unique patients this month
        $count = Tindakan::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal_tindakan', now()->month)
            ->distinct('pasien_id')
            ->count();
            
        return $count > 0 ? (string) $count : null;
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}