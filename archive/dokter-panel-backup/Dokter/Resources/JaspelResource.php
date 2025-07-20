<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\JaspelResource\Pages;
use App\Models\Tindakan;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JaspelResource extends Resource
{
    protected static ?string $model = Tindakan::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'My Jaspel';
    protected static ?string $modelLabel = 'Jaspel';
    protected static ?string $pluralModelLabel = 'Jaspel & Earnings';
    protected static ?string $navigationGroup = '💰 Earnings & Jaspel';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
        
        // Only show tindakan with jasa_dokter > 0 for this doctor
        return parent::getEloquentQuery()
            ->where('dokter_id', $dokter->id)
            ->where('jasa_dokter', '>', 0)
            ->with(['pasien', 'jenisTindakan']);
    }

    public static function form(Form $form): Form
    {
        // Read-only resource for doctors
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
                    ->limit(25),
                    
                Tables\Columns\TextColumn::make('jenisTindakan.nama_tindakan')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif Total')
                    ->money('IDR')
                    ->alignment('right')
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('jasa_dokter')
                    ->label('Jaspel Dokter')
                    ->money('IDR')
                    ->alignment('right')
                    ->color('success')
                    ->weight('bold'),
                    
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
                    
                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Tanggal Validasi')
                    ->date('d M Y')
                    ->placeholder('Belum divalidasi')
                    ->color('gray'),
                    
                // Calculate percentage of jaspel from tarif
                Tables\Columns\TextColumn::make('jaspel_percentage')
                    ->label('% Jaspel')
                    ->state(function (Tindakan $record): string {
                        if ($record->tarif > 0) {
                            $percentage = ($record->jasa_dokter / $record->tarif) * 100;
                            return number_format($percentage, 1) . '%';
                        }
                        return '0%';
                    })
                    ->alignment('center')
                    ->color('info'),
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
                    
                Tables\Filters\Filter::make('disetujui_saja')
                    ->label('Disetujui Saja')
                    ->query(fn (Builder $query): Builder => $query->where('status_validasi', 'disetujui'))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('jaspel_tinggi')
                    ->label('Jaspel > 100k')
                    ->query(fn (Builder $query): Builder => $query->where('jasa_dokter', '>', 100000))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->emptyStateHeading('Belum Ada Jaspel')
            ->emptyStateDescription('Anda belum memiliki tindakan dengan jaspel yang tercatat.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->headerActions([
                Tables\Actions\Action::make('summary')
                    ->label('Ringkasan Jaspel')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->modalHeading('Ringkasan Jaspel')
                    ->modalContent(function () {
                        $user = Auth::user();
                        $dokter = Dokter::where('user_id', $user->id)->first();
                        
                        if (!$dokter) {
                            return view('filament.dokter.modals.jaspel-summary-error');
                        }
                        
                        $thisMonth = Carbon::now()->startOfMonth();
                        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
                        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
                        $thisYear = Carbon::now()->startOfYear();
                        
                        $stats = [
                            'this_month_total' => Tindakan::where('dokter_id', $dokter->id)
                                ->where('tanggal_tindakan', '>=', $thisMonth)
                                ->where('status_validasi', 'disetujui')
                                ->sum('jasa_dokter'),
                                
                            'this_month_pending' => Tindakan::where('dokter_id', $dokter->id)
                                ->where('tanggal_tindakan', '>=', $thisMonth)
                                ->where('status_validasi', 'pending')
                                ->sum('jasa_dokter'),
                                
                            'last_month_total' => Tindakan::where('dokter_id', $dokter->id)
                                ->whereBetween('tanggal_tindakan', [$lastMonth, $lastMonthEnd])
                                ->where('status_validasi', 'disetujui')
                                ->sum('jasa_dokter'),
                                
                            'year_total' => Tindakan::where('dokter_id', $dokter->id)
                                ->where('tanggal_tindakan', '>=', $thisYear)
                                ->where('status_validasi', 'disetujui')
                                ->sum('jasa_dokter'),
                                
                            'highest_single' => Tindakan::where('dokter_id', $dokter->id)
                                ->where('status_validasi', 'disetujui')
                                ->max('jasa_dokter'),
                                
                            'average_per_procedure' => Tindakan::where('dokter_id', $dokter->id)
                                ->where('status_validasi', 'disetujui')
                                ->where('jasa_dokter', '>', 0)
                                ->avg('jasa_dokter'),
                        ];
                        
                        return view('filament.dokter.modals.jaspel-summary', compact('stats'));
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJaspels::route('/'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        if (!$dokter) {
            return null;
        }
        
        $thisMonth = Tindakan::where('dokter_id', $dokter->id)
            ->whereMonth('tanggal_tindakan', now()->month)
            ->where('status_validasi', 'disetujui')
            ->sum('jasa_dokter');
            
        if ($thisMonth > 0) {
            return 'Rp ' . number_format($thisMonth / 1000, 0) . 'k';
        }
        
        return null;
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}