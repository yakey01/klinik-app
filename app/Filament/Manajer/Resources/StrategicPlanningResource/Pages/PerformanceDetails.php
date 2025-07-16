<?php

namespace App\Filament\Manajer\Resources\StrategicPlanningResource\Pages;

use App\Filament\Manajer\Resources\StrategicPlanningResource;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\JenisTindakan;
use Filament\Resources\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PerformanceDetails extends Page
{
    protected static string $resource = StrategicPlanningResource::class;

    protected static string $view = 'filament.manajer.pages.performance-details';

    public Pegawai $record;

    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets temporarily removed until classes are created
            // PerformanceDetailsStats::class,
            // PerformanceHistoryChart::class,
            // ProcedureBreakdownChart::class,
        ];
    }

    public function getTitle(): string
    {
        return "Performance Details - {$this->record->nama_lengkap}";
    }

    public function getHeading(): string
    {
        return "📊 Performance Analytics";
    }

    public function getSubheading(): string
    {
        return "Detailed performance metrics and trends for {$this->record->nama_lengkap}";
    }

    protected function getViewData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Current month stats
        $monthlyProcedures = Tindakan::where(function($query) {
            $query->where('paramedis_id', $this->record->id)
                  ->orWhere('non_paramedis_id', $this->record->id);
        })
        ->whereMonth('created_at', $currentMonth)
        ->whereYear('created_at', $currentYear)
        ->count();

        $monthlyRevenue = Pendapatan::whereHas('tindakan', function($query) {
            $query->where('paramedis_id', $this->record->id)
                  ->orWhere('non_paramedis_id', $this->record->id);
        })
        ->whereMonth('created_at', $currentMonth)
        ->whereYear('created_at', $currentYear)
        ->sum('nominal');

        // Historical data (last 12 months)
        $historicalData = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $procedures = Tindakan::where(function($query) {
                $query->where('paramedis_id', $this->record->id)
                      ->orWhere('non_paramedis_id', $this->record->id);
            })
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();

            $revenue = Pendapatan::whereHas('tindakan', function($query) {
                $query->where('paramedis_id', $this->record->id)
                      ->orWhere('non_paramedis_id', $this->record->id);
            })
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->sum('nominal');

            return [
                'month' => $date->format('M Y'),
                'procedures' => $procedures,
                'revenue' => $revenue,
            ];
        });

        // Procedure types breakdown
        $procedureTypes = DB::table('tindakan')
            ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->where(function($query) {
                $query->where('tindakan.paramedis_id', $this->record->id)
                      ->orWhere('tindakan.non_paramedis_id', $this->record->id);
            })
            ->whereMonth('tindakan.created_at', $currentMonth)
            ->whereYear('tindakan.created_at', $currentYear)
            ->whereNull('tindakan.deleted_at')
            ->select('jenis_tindakan.nama', DB::raw('COUNT(*) as count'))
            ->groupBy('jenis_tindakan.nama')
            ->orderByDesc('count')
            ->get();

        // Performance benchmarks
        $averagePerformance = DB::table('pegawais')
            ->selectRaw('AVG(
                (SELECT COUNT(*) FROM tindakan 
                 WHERE strftime("%m", tindakan.created_at) = ? 
                 AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) 
                 AND tindakan.deleted_at IS NULL)
            ) as avg_procedures', [str_pad($currentMonth, 2, '0', STR_PAD_LEFT)])
            ->where('jenis_pegawai', $this->record->jenis_pegawai)
            ->first();

        return [
            'record' => $this->record,
            'monthlyProcedures' => $monthlyProcedures,
            'monthlyRevenue' => $monthlyRevenue,
            'historicalData' => $historicalData,
            'procedureTypes' => $procedureTypes,
            'averagePerformance' => $averagePerformance->avg_procedures ?? 0,
            'performanceRank' => $this->calculatePerformanceRank(),
        ];
    }

    private function calculatePerformanceRank(): int
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $myProcedures = Tindakan::where(function($query) {
            $query->where('paramedis_id', $this->record->id)
                  ->orWhere('non_paramedis_id', $this->record->id);
        })
        ->whereMonth('created_at', $currentMonth)
        ->whereYear('created_at', $currentYear)
        ->count();

        $rank = DB::table('pegawais')
            ->selectRaw('COUNT(*) + 1 as rank')
            ->whereRaw('(SELECT COUNT(*) FROM tindakan WHERE strftime("%m", tindakan.created_at) = ? AND (tindakan.paramedis_id = pegawais.id OR tindakan.non_paramedis_id = pegawais.id) AND tindakan.deleted_at IS NULL) > ?', [str_pad($currentMonth, 2, '0', STR_PAD_LEFT), $myProcedures])
            ->where('jenis_pegawai', $this->record->jenis_pegawai)
            ->first();

        return $rank->rank ?? 1;
    }
}