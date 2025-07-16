<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Dokter;
use Filament\Widgets\Widget;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Colors\Color;
use Livewire\Attributes\Reactive;

class AdminInteractiveDashboardWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.admin-interactive-dashboard-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $selectedPeriod = 'this_month';
    public ?string $selectedDepartment = 'all';
    public ?int $selectedDoctor = null;
    
    public array $period_options = [
        'today' => 'Hari Ini',
        'this_week' => 'Minggu Ini',
        'this_month' => 'Bulan Ini',
        'this_quarter' => 'Kuartal Ini',
        'this_year' => 'Tahun Ini',
        'last_week' => 'Minggu Lalu',
        'last_month' => 'Bulan Lalu',
        'last_quarter' => 'Kuartal Lalu',
        'last_year' => 'Tahun Lalu',
        'custom' => 'Rentang Khusus',
    ];
    
    public array $department_options = [
        'all' => 'Semua Departemen',
        'umum' => 'Dokter Umum',
        'gigi' => 'Dokter Gigi',
        'spesialis' => 'Spesialis',
    ];
    
    public bool $error = false;
    public string $message = '';

    public function mount(): void
    {
        $this->form->fill([
            'selectedPeriod' => $this->selectedPeriod,
            'selectedDepartment' => $this->selectedDepartment,
            'selectedDoctor' => $this->selectedDoctor,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('🏥 Kontrol Dashboard Medis')
                    ->description('Sesuaikan tampilan dashboard dengan kebutuhan analisis medis')
                    ->schema([
                        Select::make('selectedPeriod')
                            ->label('📅 Periode Analisis')
                            ->options($this->period_options)
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshData()),
                        
                        Select::make('selectedDepartment')
                            ->label('🏥 Departemen')
                            ->options($this->department_options)
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshData()),
                            
                        Select::make('selectedDoctor')
                            ->label('👨‍⚕️ Dokter')
                            ->options($this->getDoctorOptions())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshData()),
                    ])
                    ->columns(3)
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
            $this->dispatch('refreshMedicalDashboard');
        } catch (\Exception $e) {
            $this->error = true;
            $this->message = 'Terjadi kesalahan saat memuat data dashboard medis. Silakan coba lagi.';
        }
    }

    public function getMedicalSummary(): array
    {
        try {
            $dateRange = $this->getDateRange();
            
            $query = Tindakan::whereBetween('tanggal_tindakan', $dateRange);
            
            if ($this->selectedDepartment !== 'all') {
                $query->whereHas('dokter', function ($q) {
                    $q->where('spesialisasi', $this->selectedDepartment);
                });
            }
            
            if ($this->selectedDoctor) {
                $query->where('dokter_id', $this->selectedDoctor);
            }
            
            $totalProcedures = $query->count();
            $totalPatients = $query->distinct('pasien_id')->count();
            $totalRevenue = $query->sum('tarif');
            $avgTreatmentTime = $query->avg('durasi_tindakan') ?? 0;
            
            return [
                'total_procedures' => $totalProcedures,
                'total_patients' => $totalPatients,
                'total_revenue' => $totalRevenue,
                'avg_treatment_time' => round($avgTreatmentTime, 2),
                'success_rate' => $this->calculateSuccessRate($query),
                'utilization_rate' => $this->calculateUtilizationRate($query),
            ];
        } catch (\Exception $e) {
            return [
                'total_procedures' => 0,
                'total_patients' => 0,
                'total_revenue' => 0,
                'avg_treatment_time' => 0,
                'success_rate' => 0,
                'utilization_rate' => 0,
            ];
        }
    }

    public function getWeeklyTrends(): array
    {
        try {
            $weeks = [];
            $procedures = [];
            $patients = [];
            $revenue = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subWeeks($i);
                $startOfWeek = $date->startOfWeek();
                $endOfWeek = $date->endOfWeek();
                
                $weeks[] = $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d');
                
                $query = Tindakan::whereBetween('tanggal_tindakan', [$startOfWeek, $endOfWeek]);
                
                if ($this->selectedDepartment !== 'all') {
                    $query->whereHas('dokter', function ($q) {
                        $q->where('spesialisasi', $this->selectedDepartment);
                    });
                }
                
                if ($this->selectedDoctor) {
                    $query->where('dokter_id', $this->selectedDoctor);
                }
                
                $procedures[] = $query->count();
                $patients[] = $query->distinct('pasien_id')->count();
                $revenue[] = $query->sum('tarif');
            }
            
            return [
                'weeks' => $weeks,
                'procedures' => $procedures,
                'patients' => $patients,
                'revenue' => $revenue,
            ];
        } catch (\Exception $e) {
            return [
                'weeks' => [],
                'procedures' => [],
                'patients' => [],
                'revenue' => [],
            ];
        }
    }

    public function getMedicalKpiData(): array
    {
        try {
            $summary = $this->getMedicalSummary();
            $lastPeriodSummary = $this->getLastPeriodSummary();
            
            return [
                'procedures' => [
                    'value' => $summary['total_procedures'],
                    'change' => $this->calculateChange($summary['total_procedures'], $lastPeriodSummary['total_procedures']),
                    'trend' => $summary['total_procedures'] >= $lastPeriodSummary['total_procedures'] ? 'up' : 'down',
                ],
                'patients' => [
                    'value' => $summary['total_patients'],
                    'change' => $this->calculateChange($summary['total_patients'], $lastPeriodSummary['total_patients']),
                    'trend' => $summary['total_patients'] >= $lastPeriodSummary['total_patients'] ? 'up' : 'down',
                ],
                'revenue' => [
                    'value' => $summary['total_revenue'],
                    'change' => $this->calculateChange($summary['total_revenue'], $lastPeriodSummary['total_revenue']),
                    'trend' => $summary['total_revenue'] >= $lastPeriodSummary['total_revenue'] ? 'up' : 'down',
                ],
                'success_rate' => [
                    'value' => $summary['success_rate'],
                    'change' => $this->calculateChange($summary['success_rate'], $lastPeriodSummary['success_rate']),
                    'trend' => $summary['success_rate'] >= $lastPeriodSummary['success_rate'] ? 'up' : 'down',
                ],
                'utilization' => [
                    'value' => $summary['utilization_rate'],
                    'change' => $this->calculateChange($summary['utilization_rate'], $lastPeriodSummary['utilization_rate']),
                    'trend' => $summary['utilization_rate'] >= $lastPeriodSummary['utilization_rate'] ? 'up' : 'down',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'procedures' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'patients' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'revenue' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'success_rate' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
                'utilization' => ['value' => 0, 'change' => 0, 'trend' => 'up'],
            ];
        }
    }

    public function getTopPerformers(): array
    {
        try {
            $dateRange = $this->getDateRange();
            
            $topDoctors = Dokter::select('dokter.*')
                ->join('tindakan', 'dokter.id', '=', 'tindakan.dokter_id')
                ->whereBetween('tindakan.tanggal_tindakan', $dateRange)
                ->groupBy('dokter.id')
                ->orderByRaw('COUNT(tindakan.id) DESC')
                ->limit(5)
                ->get()
                ->map(function ($doctor) use ($dateRange) {
                    $procedureCount = Tindakan::where('dokter_id', $doctor->id)
                        ->whereBetween('tanggal_tindakan', $dateRange)
                        ->count();
                    
                    return [
                        'name' => $doctor->name,
                        'specialty' => $doctor->spesialisasi ?? 'Umum',
                        'procedures' => $procedureCount,
                        'revenue' => Tindakan::where('dokter_id', $doctor->id)
                            ->whereBetween('tanggal_tindakan', $dateRange)
                            ->sum('tarif'),
                    ];
                });
            
            $topProcedures = Tindakan::select('jenis_tindakan.nama', \DB::raw('COUNT(tindakan.id) as total_count'))
                ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
                ->whereBetween('tindakan.tanggal_tindakan', $dateRange)
                ->groupBy('jenis_tindakan.id', 'jenis_tindakan.nama')
                ->orderBy('total_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($procedure) {
                    return [
                        'name' => $procedure->nama,
                        'total' => $procedure->total_count,
                    ];
                });
            
            return [
                'doctors' => $topDoctors,
                'procedures' => $topProcedures,
            ];
        } catch (\Exception $e) {
            return [
                'doctors' => [],
                'procedures' => [],
            ];
        }
    }

    private function getDateRange(): array
    {
        $now = now();
        
        return match($this->selectedPeriod) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'this_week' => [$now->startOfWeek(), $now->endOfWeek()],
            'this_month' => [$now->startOfMonth(), $now->endOfMonth()],
            'this_quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'last_week' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'last_month' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            'last_quarter' => [$now->subQuarter()->startOfQuarter(), $now->subQuarter()->endOfQuarter()],
            'last_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function getLastPeriodSummary(): array
    {
        $lastPeriod = match($this->selectedPeriod) {
            'today' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'this_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
        };
        
        $query = Tindakan::whereBetween('tanggal_tindakan', $lastPeriod);
        
        if ($this->selectedDepartment !== 'all') {
            $query->whereHas('dokter', function ($q) {
                $q->where('spesialisasi', $this->selectedDepartment);
            });
        }
        
        if ($this->selectedDoctor) {
            $query->where('dokter_id', $this->selectedDoctor);
        }
        
        return [
            'total_procedures' => $query->count(),
            'total_patients' => $query->distinct('pasien_id')->count(),
            'total_revenue' => $query->sum('tarif'),
            'success_rate' => $this->calculateSuccessRate($query),
            'utilization_rate' => $this->calculateUtilizationRate($query),
        ];
    }

    private function calculateSuccessRate($query): float
    {
        $total = $query->count();
        if ($total === 0) return 0;
        
        $successful = $query->where('status_validasi', 'disetujui')->count();
        return round(($successful / $total) * 100, 2);
    }

    private function calculateUtilizationRate($query): float
    {
        // Simplified calculation - in real implementation, this would compare to capacity
        $total = $query->count();
        $capacity = 100; // This should be dynamic based on actual capacity
        
        return round(($total / $capacity) * 100, 2);
    }

    private function calculateChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function getDoctorOptions(): array
    {
        return Dokter::pluck('name', 'id')->toArray();
    }

    public function updatedSelectedPeriod(): void
    {
        $this->refreshData();
    }

    public function updatedSelectedDepartment(): void
    {
        $this->refreshData();
    }

    public function updatedSelectedDoctor(): void
    {
        $this->refreshData();
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
}