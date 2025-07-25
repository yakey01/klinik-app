<?php

namespace App\Filament\Bendahara\Widgets;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Filament\Widgets\Widget;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Colors\Color;
use Livewire\Attributes\Reactive;

class InteractiveDashboardWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.bendahara.widgets.interactive-dashboard-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $selectedPeriod = 'this_month';
    public ?int $selectedMonths = 6;
    
    public array $period_options = [
        'this_month' => 'Bulan Ini',
        'this_quarter' => 'Kuartal Ini',
        'this_year' => 'Tahun Ini',
        'last_month' => 'Bulan Lalu',
        'last_quarter' => 'Kuartal Lalu',
        'last_year' => 'Tahun Lalu',
        'custom' => 'Rentang Khusus',
    ];
    
    public bool $error = false;
    public string $message = '';

    public function mount(): void
    {
        $this->form->fill([
            'selectedPeriod' => $this->selectedPeriod,
            'selectedMonths' => $this->selectedMonths,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Kontrol Dashboard')
                    ->schema([
                        Select::make('selectedPeriod')
                            ->label('Periode')
                            ->options($this->period_options)
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshData()),
                        
                        Select::make('selectedMonths')
                            ->label('Rentang Bulan')
                            ->options([
                                6 => '6 Bulan',
                                12 => '12 Bulan',
                                18 => '18 Bulan',
                                24 => '24 Bulan',
                            ])
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshData()),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public function refreshData(): void
    {
        try {
            $this->error = false;
            $this->message = '';
            
            // Trigger re-render of the widget
            $this->dispatch('refreshDashboard');
        } catch (\Exception $e) {
            $this->error = true;
            $this->message = 'Terjadi kesalahan saat memuat data dashboard. Silakan coba lagi.';
        }
    }

    public function getFinancialSummary(): array
    {
        try {
            $dateRange = $this->getDateRange();
            
            return [
                'total_pendapatan' => Pendapatan::whereBetween('tanggal', $dateRange)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal'),
                'total_pengeluaran' => Pengeluaran::whereBetween('tanggal', $dateRange)
                    ->sum('nominal'),
                'total_jaspel' => Jaspel::whereBetween('tanggal', $dateRange)
                    ->sum('nominal'),
                'net_profit' => 0, // Will be calculated
            ];
        } catch (\Exception $e) {
            return [
                'total_pendapatan' => 0,
                'total_pengeluaran' => 0,
                'total_jaspel' => 0,
                'net_profit' => 0,
            ];
        }
    }

    public function getMonthlyTrends(): array
    {
        try {
            $months = [];
            $pendapatan = [];
            $pengeluaran = [];
            
            for ($i = $this->selectedMonths - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthlyPendapatan = Pendapatan::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                
                $monthlyPengeluaran = Pengeluaran::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                $pendapatan[] = $monthlyPendapatan;
                $pengeluaran[] = $monthlyPengeluaran;
            }
            
            return [
                'months' => $months,
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
            ];
        } catch (\Exception $e) {
            return [
                'months' => [],
                'pendapatan' => [],
                'pengeluaran' => [],
            ];
        }
    }

    public function getKpiData(): array
    {
        try {
            $summary = $this->getFinancialSummary();
            $lastMonthSummary = $this->getLastMonthSummary();
            
            return [
                'pendapatan' => [
                    'value' => $summary['total_pendapatan'],
                    'change' => $this->calculateChange($summary['total_pendapatan'], $lastMonthSummary['total_pendapatan']),
                    'trend' => $summary['total_pendapatan'] >= $lastMonthSummary['total_pendapatan'] ? 'up' : 'down',
                ],
                'pengeluaran' => [
                    'value' => $summary['total_pengeluaran'],
                    'change' => $this->calculateChange($summary['total_pengeluaran'], $lastMonthSummary['total_pengeluaran']),
                    'trend' => $summary['total_pengeluaran'] >= $lastMonthSummary['total_pengeluaran'] ? 'up' : 'down',
                ],
                'profit' => [
                    'value' => $summary['total_pendapatan'] - $summary['total_pengeluaran'],
                    'change' => 0,
                    'trend' => 'up',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'pendapatan' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'pengeluaran' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'profit' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
            ];
        }
    }

    private function getDateRange(): array
    {
        $now = now();
        
        return match($this->selectedPeriod) {
            'this_month' => [$now->startOfMonth(), $now->endOfMonth()],
            'this_quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'last_month' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            'last_quarter' => [$now->subQuarter()->startOfQuarter(), $now->subQuarter()->endOfQuarter()],
            'last_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function getLastMonthSummary(): array
    {
        $lastMonth = now()->subMonth();
        
        return [
            'total_pendapatan' => Pendapatan::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal'),
            'total_pengeluaran' => Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal'),
        ];
    }

    private function calculateChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function updatedSelectedPeriod(): void
    {
        $this->refreshData();
    }

    public function updatedSelectedMonths(): void
    {
        $this->refreshData();
    }
}