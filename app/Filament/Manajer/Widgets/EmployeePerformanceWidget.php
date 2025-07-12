<?php

namespace App\Filament\Manajer\Widgets;

use App\Models\User;
use App\Models\Tindakan;
use App\Models\Jaspel;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class EmployeePerformanceWidget extends BaseWidget
{
    protected static ?string $heading = '👨‍⚕️ Kinerja Pegawai / Dokter';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return $table
            ->query(
                User::query()
                    ->whereHas('role', function (Builder $query) {
                        $query->whereIn('name', ['dokter', 'paramedis', 'perawat']);
                    })
                    ->where('is_active', true)
                    ->withCount([
                        'tindakanAsDokter as jumlah_tindakan_dokter' => function (Builder $query) use ($currentMonth, $endOfMonth) {
                            $query->whereBetween('created_at', [$currentMonth, $endOfMonth]);
                        },
                        'tindakanAsParamedis as jumlah_tindakan_paramedis' => function (Builder $query) use ($currentMonth, $endOfMonth) {
                            $query->whereBetween('created_at', [$currentMonth, $endOfMonth]);
                        }
                    ])
                    ->withSum([
                        'jaspel as total_jaspel' => function (Builder $query) use ($currentMonth, $endOfMonth) {
                            $query->whereBetween('tanggal', [$currentMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')]);
                        }
                    ], 'nominal')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state . ' (' . $record->role?->name . ')'),

                Tables\Columns\TextColumn::make('jumlah_tindakan_total')
                    ->label('Jumlah Tindakan')
                    ->getStateUsing(function ($record) {
                        return ($record->jumlah_tindakan_dokter ?? 0) + ($record->jumlah_tindakan_paramedis ?? 0);
                    })
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_pasien')
                    ->label('Total Pasien')
                    ->getStateUsing(function ($record) use ($currentMonth, $endOfMonth) {
                        $pasienSebagaiDokter = Tindakan::where('dokter_id', $record->id)
                            ->whereBetween('created_at', [$currentMonth, $endOfMonth])
                            ->distinct('pasien_id')
                            ->count('pasien_id');

                        $pasienSebagaiParamedis = Tindakan::where('paramedis_id', $record->id)
                            ->whereBetween('created_at', [$currentMonth, $endOfMonth])
                            ->distinct('pasien_id')
                            ->count('pasien_id');

                        return $pasienSebagaiDokter + $pasienSebagaiParamedis;
                    })
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_jaspel')
                    ->label('Jasa Pelayanan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->alignEnd()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        return $state > 1000000 ? 'success' : ($state > 500000 ? 'warning' : 'info');
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('performance_indicator')
                    ->label('Indikator Performa')
                    ->getStateUsing(function ($record) {
                        $totalTindakan = ($record->jumlah_tindakan_dokter ?? 0) + ($record->jumlah_tindakan_paramedis ?? 0);
                        $totalJaspel = $record->total_jaspel ?? 0;
                        
                        if ($totalTindakan >= 50 && $totalJaspel >= 1000000) return '🌟 Excellent';
                        if ($totalTindakan >= 30 && $totalJaspel >= 750000) return '⭐ Very Good';
                        if ($totalTindakan >= 20 && $totalJaspel >= 500000) return '👍 Good';
                        if ($totalTindakan >= 10) return '📈 Average';
                        return '📊 Below Average';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            '🌟 Excellent' => 'success',
                            '⭐ Very Good' => 'info',
                            '👍 Good' => 'warning',
                            '📈 Average' => 'gray',
                            default => 'danger'
                        };
                    }),
            ])
            ->defaultSort('total_jaspel', 'desc')
            ->striped()
            ->paginated([10, 25])
            ->poll('30s');
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}