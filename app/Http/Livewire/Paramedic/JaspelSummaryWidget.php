<?php

namespace App\Http\Livewire\Paramedic;

use Livewire\Component;
use App\Services\JaspelCalculationService;
use Illuminate\Support\Facades\Auth;

class JaspelSummaryWidget extends Component
{
    public $currentMonthTotal = 0;
    public $pendingAmount = 0;
    public $approvedAmount = 0;
    public $rejectedAmount = 0;
    public $monthlyTrend = [];
    public $isLoading = true;
    public $shiftBreakdown = [];

    protected $jaspelService;

    public function boot(JaspelCalculationService $jaspelService)
    {
        $this->jaspelService = $jaspelService;
    }

    public function mount()
    {
        $this->loadJaspelData();
    }

    public function loadJaspelData()
    {
        $this->isLoading = true;
        
        $user = Auth::user();
        $statistics = $this->jaspelService->getJaspelStatistics($user);
        
        // Set summary data
        $this->currentMonthTotal = $statistics['current_month']['total'];
        $this->pendingAmount = $statistics['current_month']['pending'];
        $this->approvedAmount = $statistics['current_month']['approved'];
        $this->rejectedAmount = $statistics['current_month']['rejected'];
        
        // Set trend data for chart
        $this->monthlyTrend = $statistics['trend'];
        
        // Get shift breakdown for paramedics
        $this->loadShiftBreakdown();
        
        $this->isLoading = false;
    }

    private function loadShiftBreakdown()
    {
        // Get Jaspel breakdown by shift for current month
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $shiftData = \App\Models\Jaspel::where('user_id', Auth::id())
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->where('status', 'approved')
            ->whereHas('tindakan', function($query) {
                $query->whereNotNull('shift_id');
            })
            ->with(['tindakan.shift'])
            ->get()
            ->groupBy(function($jaspel) {
                return $jaspel->tindakan->shift->nama_shift ?? 'Lainnya';
            })
            ->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('nominal')
                ];
            });
            
        $this->shiftBreakdown = $shiftData->toArray();
    }

    public function refreshData()
    {
        $this->loadJaspelData();
        $this->dispatchBrowserEvent('jaspel-refreshed');
    }

    public function render()
    {
        return view('livewire.paramedic.jaspel-summary-widget');
    }
}