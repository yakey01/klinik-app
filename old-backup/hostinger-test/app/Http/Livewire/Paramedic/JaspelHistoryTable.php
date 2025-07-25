<?php

namespace App\Http\Livewire\Paramedic;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\JaspelCalculationService;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JaspelHistoryTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $monthFilter = '';
    public $yearFilter = '';
    public $shiftFilter = '';
    public $perPage = 10;
    public $sortField = 'tanggal_pengajuan';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'monthFilter' => ['except' => ''],
        'yearFilter' => ['except' => ''],
        'shiftFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    protected $jaspelService;

    public function boot(JaspelCalculationService $jaspelService)
    {
        $this->jaspelService = $jaspelService;
    }

    public function mount()
    {
        $this->monthFilter = now()->month;
        $this->yearFilter = now()->year;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingMonthFilter()
    {
        $this->resetPage();
    }

    public function updatingYearFilter()
    {
        $this->resetPage();
    }

    public function updatingShiftFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function exportToExcel()
    {
        // Implementation for Excel export
        $this->dispatchBrowserEvent('notify', [
            'type' => 'info',
            'message' => 'Fitur export Excel akan segera tersedia'
        ]);
    }

    public function exportToPdf()
    {
        // Implementation for PDF export
        $this->dispatchBrowserEvent('notify', [
            'type' => 'info',
            'message' => 'Fitur export PDF akan segera tersedia'
        ]);
    }

    public function getRowsProperty()
    {
        $filters = [
            'status' => $this->statusFilter,
            'month' => $this->monthFilter,
            'year' => $this->yearFilter,
        ];

        $query = $this->jaspelService->getJaspelHistory(Auth::user(), $filters);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('keterangan', 'like', '%' . $this->search . '%')
                  ->orWhere('nominal', 'like', '%' . $this->search . '%');
            });
        }

        // Apply shift filter for paramedics
        if ($this->shiftFilter) {
            $query->whereHas('tindakan.shift', function($q) {
                $q->where('nama_shift', $this->shiftFilter);
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function getShiftOptions()
    {
        return \App\Models\ShiftTemplate::pluck('nama_shift', 'nama_shift')->toArray();
    }

    public function render()
    {
        return view('livewire.paramedic.jaspel-history-table', [
            'jaspelRecords' => $this->rows,
            'months' => $this->getMonthOptions(),
            'years' => $this->getYearOptions(),
            'shifts' => $this->getShiftOptions(),
        ]);
    }

    private function getMonthOptions()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create()->month($i)->format('F');
        }
        return $months;
    }

    private function getYearOptions()
    {
        $currentYear = now()->year;
        $years = [];
        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
            $years[$i] = $i;
        }
        return $years;
    }
}