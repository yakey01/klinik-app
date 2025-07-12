<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\AnalyticsKinerjaResource\Pages;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\PendapatanHarian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AnalyticsKinerjaResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = '📈 Performance Analytics';

    protected static ?string $navigationLabel = '📊 Analytics Kinerja';

    protected static ?string $modelLabel = 'Analytics Kinerja';

    protected static ?string $pluralModelLabel = 'Performance Analytics';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('KPI Analysis Parameters')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('📅 Periode Mulai')
                            ->default(now()->startOfMonth()),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('📅 Periode Akhir')
                            ->default(now()->endOfMonth()),

                        Forms\Components\Select::make('analysis_type')
                            ->label('📊 Jenis Analisis')
                            ->options([
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan', 
                                'monthly' => 'Bulanan',
                                'quarterly' => 'Kuartalan',
                            ])
                            ->default('monthly'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('📊 Performance Analytics Dashboard')
            ->description('Detailed KPI analysis dan performance metrics untuk strategic decision making')
            ->query(
                Tindakan::query()
                    ->with(['dokter', 'paramedis', 'jenisTindakan', 'pasien', 'shift'])
                    ->where('status', 'selesai')
                    ->where('status_validasi', 'disetujui')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('dokter.name')
                    ->label('👨‍⚕️ Dokter')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('paramedis.name')
                    ->label('👩‍⚕️ Paramedis')
                    ->searchable()
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('🏥 Jenis Tindakan')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('jenisTindakan.kategori')
                    ->label('📂 Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'info',
                        'gigi' => 'warning',
                        'bpjs' => 'primary',
                        'darurat' => 'danger',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('💰 Tarif')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('jasa_dokter')
                    ->label('👨‍⚕️ Jasa Dokter')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('jasa_paramedis')
                    ->label('👩‍⚕️ Jasa Paramedis')
                    ->money('IDR')
                    ->sortable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('shift.nama')
                    ->label('⏰ Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Sore' => 'warning',
                        'Malam' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('efficiency_score')
                    ->label('⚡ Efficiency Score')
                    ->getStateUsing(function ($record) {
                        // Calculate efficiency based on tarif vs time spent
                        $baseScore = ($record->tarif / 100000) * 20; // Base efficiency calculation
                        $timeBonus = $record->shift?->nama === 'Malam' ? 10 : 0; // Night shift bonus
                        return min(100, round($baseScore + $timeBonus));
                    })
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state . '%'),

                Tables\Columns\TextColumn::make('revenue_contribution')
                    ->label('💡 Revenue Impact')
                    ->getStateUsing(function ($record) {
                        $monthlyTotal = Tindakan::whereMonth('tanggal_tindakan', $record->tanggal_tindakan->month)
                            ->whereYear('tanggal_tindakan', $record->tanggal_tindakan->year)
                            ->where('status_validasi', 'disetujui')
                            ->sum('tarif');
                        
                        if ($monthlyTotal > 0) {
                            $percentage = ($record->tarif / $monthlyTotal) * 100;
                            return round($percentage, 2) . '%';
                        }
                        return '0%';
                    })
                    ->badge()
                    ->color(function ($state) {
                        $value = floatval(str_replace('%', '', $state));
                        return $value >= 5 ? 'success' : ($value >= 2 ? 'warning' : 'gray');
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dokter')
                    ->label('👨‍⚕️ Filter Dokter')
                    ->relationship('dokter', 'name'),

                Tables\Filters\SelectFilter::make('kategori')
                    ->label('📂 Kategori Tindakan')
                    ->options([
                        'umum' => 'Umum',
                        'gigi' => 'Gigi', 
                        'bpjs' => 'BPJS',
                        'darurat' => 'Darurat',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            return $query->whereHas('jenisTindakan', function (Builder $q) use ($data) {
                                $q->where('kategori', $data['value']);
                            });
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('shift')
                    ->label('⏰ Filter Shift')
                    ->relationship('shift', 'nama'),

                Filter::make('high_value')
                    ->label('💎 High Value (>500k)')
                    ->query(fn (Builder $query): Builder => $query->where('tarif', '>', 500000)),

                Filter::make('this_month')
                    ->label('📆 Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_tindakan', [now()->startOfMonth(), now()->endOfMonth()]))
                    ->default(),

                Filter::make('last_month')
                    ->label('📆 Bulan Lalu')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_tindakan', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])),

                Filter::make('quarter')
                    ->label('📊 Kuartal Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_tindakan', [now()->startOfQuarter(), now()->endOfQuarter()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️ Detail')
                    ->color('info'),

                Tables\Actions\Action::make('analyze_performance')
                    ->label('📊 Analyze')
                    ->icon('heroicon-m-chart-bar')
                    ->color('success')
                    ->action(function ($record) {
                        $efficiency = ($record->tarif / 100000) * 20;
                        $efficiency = min(100, round($efficiency + ($record->shift?->nama === 'Malam' ? 10 : 0)));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('📊 Performance Analysis')
                            ->body("Efficiency Score: {$efficiency}% | Revenue: Rp " . number_format($record->tarif, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_analytics')
                        ->label('📊 Export Analytics')
                        ->icon('heroicon-m-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            $totalRevenue = $records->sum('tarif');
                            $avgEfficiency = $records->avg(function ($record) {
                                return ($record->tarif / 100000) * 20;
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('📊 Analytics Export Complete')
                                ->body("Total Revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . " | Avg Efficiency: " . round($avgEfficiency) . "%")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('performance_benchmark')
                        ->label('🎯 Benchmark Analysis')
                        ->icon('heroicon-m-trophy')
                        ->color('warning')
                        ->action(function ($records) {
                            $topPerformer = $records->sortByDesc('tarif')->first();
                            $avgRevenue = $records->avg('tarif');
                            
                            \Filament\Notifications\Notification::make()
                                ->title('🎯 Benchmark Analysis')
                                ->body("Top Revenue: Rp " . number_format($topPerformer->tarif, 0, ',', '.') . " | Average: Rp " . number_format($avgRevenue, 0, ',', '.'))
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('300s'); // Poll every 5 minutes for analytics
    }

    public static function getNavigationBadge(): ?string
    {
        $thisMonth = static::getModel()::whereBetween('tanggal_tindakan', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status_validasi', 'disetujui')
            ->count();
        return $thisMonth > 0 ? (string) $thisMonth : null;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnalyticsKinerjas::route('/'),
            'view' => Pages\ViewAnalyticsKinerja::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Analytics is view-only
    }

    public static function canEdit($record): bool
    {
        return false; // Analytics is view-only
    }

    public static function canDelete($record): bool
    {
        return false; // Analytics is view-only
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}