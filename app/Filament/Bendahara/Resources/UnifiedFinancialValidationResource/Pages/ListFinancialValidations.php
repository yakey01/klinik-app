<?php

namespace App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource\Pages;

use App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class ListFinancialValidations extends ListRecords
{
    protected static string $resource = UnifiedFinancialValidationResource::class;

    protected $activeFinancialTab = 'pendapatan'; // Track which financial model is active

    public function mount(): void
    {
        parent::mount();
        
        // Only update session if activeTab is explicitly passed in URL
        if (request()->has('activeTab')) {
            session(['financial_validation_active_tab' => request()->get('activeTab')]);
        }
        
        // Get the active tab from session or default to pendapatan
        $this->activeFinancialTab = session('financial_validation_active_tab', 'pendapatan');
    }

    protected function getActiveTab(): string
    {
        return $this->activeFinancialTab;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Simple refresh button only
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->size('sm')
                ->tooltip('Muat Ulang')
                ->action(fn () => redirect(request()->header('Referer'))),
        ];
    }

    public function getTabs(): array
    {
        return [
            // Simple status-based tabs only
            'all' => Tab::make('Semua Data'),
                
            'pending' => Tab::make('Tertunda')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui')),
        ];
    }


    public function getSubheading(): ?string
    {
        return 'Validasi transaksi keuangan utama (Pendapatan dan Pengeluaran). Untuk validasi transaksi harian, gunakan menu "Validasi Transaksi Harian".';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // NO WIDGETS - Pure Filament only
        ];
    }

    // Override the table query to handle model switching
    protected function getTableQuery(): Builder
    {
        $activeTab = $this->getActiveTab();
        
        if ($activeTab === 'pengeluaran') {
            return Pengeluaran::query()
                ->whereNotNull('input_by')
                ->with(['inputBy', 'validasiBy']);
        } else {
            return Pendapatan::query()
                ->whereNotNull('input_by')
                ->with(['inputBy', 'validasiBy', 'tindakan']);
        }
    }

    public function getTitle(): string
    {
        return 'Pusat Validasi Keuangan';
    }

}