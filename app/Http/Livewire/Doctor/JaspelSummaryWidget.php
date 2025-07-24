<?php

namespace App\Http\Livewire\Doctor;

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
        
        $this->isLoading = false;
    }

    public function refreshData()
    {
        $this->loadJaspelData();
        $this->dispatchBrowserEvent('jaspel-refreshed');
    }

    public function render()
    {
        return view('livewire.doctor.jaspel-summary-widget');
    }
}