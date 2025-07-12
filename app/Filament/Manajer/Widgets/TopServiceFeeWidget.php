<?php

namespace App\Filament\Manajer\Widgets;

use App\Models\User;
use App\Models\Jaspel;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopServiceFeeWidget extends BaseWidget
{
    protected static ?string $heading = '🏆 Top 5 Jasa Pelayanan Terbesar';

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
                    ->whereHas('jaspel', function (Builder $query) use ($currentMonth, $endOfMonth) {
                        $query->whereBetween('tanggal', [$currentMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')]);
                    })
                    ->withSum([
                        'jaspel as total_jp' => function (Builder $query) use ($currentMonth, $endOfMonth) {
                            $query->whereBetween('tanggal', [$currentMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')]);
                        }
                    ], 'nominal')
                    ->orderByDesc('total_jp')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->getStateUsing(function ($record, $rowLoop) {
                        $rank = $rowLoop->iteration;
                        return match ($rank) {
                            1 => '🥇 #1',
                            2 => '🥈 #2', 
                            3 => '🥉 #3',
                            default => "#{$rank}"
                        };
                    })
                    ->alignCenter()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->formatStateUsing(function ($state, $record) {
                        $roleIcon = match ($record->role?->name) {
                            'dokter' => '👨‍⚕️',
                            'paramedis' => '👩‍⚕️',
                            'perawat' => '👨‍⚕️',
                            default => '👤'
                        };
                        return $roleIcon . ' ' . $state;
                    })
                    ->searchable()
                    ->weight('medium')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Posisi')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'dokter' => 'success',
                            'paramedis' => 'info',
                            'perawat' => 'warning',
                            default => 'gray'
                        };
                    }),

                Tables\Columns\TextColumn::make('total_jp')
                    ->label('Total JP')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->alignEnd()
                    ->weight('bold')
                    ->size('lg')
                    ->color(function ($state, $rowLoop) {
                        return match ($rowLoop->iteration) {
                            1 => 'success',
                            2 => 'info', 
                            3 => 'warning',
                            default => 'gray'
                        };
                    }),

                Tables\Columns\TextColumn::make('achievement_badge')
                    ->label('Achievement')
                    ->getStateUsing(function ($record, $rowLoop) {
                        $totalJp = $record->total_jp ?? 0;
                        $rank = $rowLoop->iteration;
                        
                        if ($rank === 1 && $totalJp >= 2000000) return '💎 Diamond';
                        if ($rank <= 2 && $totalJp >= 1500000) return '🏅 Platinum';
                        if ($rank <= 3 && $totalJp >= 1000000) return '🥇 Gold';
                        if ($totalJp >= 750000) return '🥈 Silver';
                        return '🥉 Bronze';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            '💎 Diamond' => 'success',
                            '🏅 Platinum' => 'info',
                            '🥇 Gold' => 'warning',
                            '🥈 Silver' => 'gray',
                            default => 'danger'
                        };
                    }),
            ])
            ->striped()
            ->paginated(false)
            ->poll('60s')
            ->defaultSort('total_jp', 'desc');
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}