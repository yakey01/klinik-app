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
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

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
            // Model switch buttons (only show when needed)
            Action::make('switch_to_pendapatan')
                ->label('Penerimaan')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->tooltip('Beralih ke Validasi Penerimaan')
                ->size('sm')
                ->url(fn () => static::getResource()::getUrl('index', ['activeTab' => 'pendapatan']))
                ->visible(fn () => $this->getActiveTab() !== 'pendapatan'),
                
            Action::make('switch_to_pengeluaran')
                ->label('Pengeluaran')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->tooltip('Beralih ke Validasi Pengeluaran')
                ->size('sm')
                ->url(fn () => static::getResource()::getUrl('index', ['activeTab' => 'pengeluaran']))
                ->visible(fn () => $this->getActiveTab() !== 'pengeluaran'),
                
            // Action group for quick access
            Actions\ActionGroup::make([
                Action::make('financial_statistics')
                    ->label('Statistik')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('info')
                    ->modalHeading('📊 Statistik Validasi Keuangan')
                    ->modalContent(view('filament.bendahara.financial-validation-stats', [
                        'stats' => $this->getFinancialStats()
                    ]))
                    ->modalWidth('4xl'),
                    
                Action::make('quick_actions')
                    ->label('Aksi Cepat')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->modalHeading('⚡ Aksi Validasi Keuangan Cepat')
                    ->modalDescription('Lakukan operasi batch pada validasi yang tertunda')
                    ->form([
                        Forms\Components\Select::make('transaction_type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'pendapatan' => 'Penerimaan',
                                'pengeluaran' => 'Pengeluaran', 
                                'both' => 'Keduanya',
                            ])
                            ->default($this->getActiveTab())
                            ->required(),
                            
                        Forms\Components\Select::make('action_type')
                            ->label('Jenis Aksi')
                            ->options([
                                'approve_low_value' => 'Setujui otomatis < 500K',
                                'approve_routine' => 'Setujui transaksi rutin',
                                'flag_high_value' => 'Tandai nilai tinggi (>5M)',
                                'categorize_by_amount' => 'Kategorisasi otomatis berdasarkan jumlah',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->performQuickFinancialAction($data['transaction_type'], $data['action_type']);
                    }),
                    
                Action::make('export_current_view')
                    ->label('Ekspor')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('export_format')
                            ->label('Format Ekspor')
                            ->options([
                                'xlsx' => 'Excel (.xlsx)',
                                'csv' => 'CSV (.csv)',
                                'pdf' => 'Financial Report PDF'
                            ])
                            ->default('xlsx')
                            ->required(),
                            
                        Forms\Components\Select::make('export_scope')
                            ->label('Cakupan Ekspor')
                            ->options([
                                'current_tab' => 'Tab Aktif Saja',
                                'both_types' => 'Penerimaan & Pengeluaran',
                                'pending_only' => 'Hanya Yang Pending',
                            ])
                            ->default('current_tab')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->exportFinancialView($data['export_format'], $data['export_scope']);
                    }),
            ])
            ->label('Aksi')
            ->icon('heroicon-o-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
            
            // Refresh button - standalone icon
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
        $stats = $this->getFinancialStats();
        $activeTab = $this->getActiveTab();
        $currentStats = $activeTab === 'pengeluaran' ? $stats['pengeluaran'] : $stats['pendapatan'];
        
        return [
            // Status-based filtering tabs
            'all' => Tab::make('Semua Data')
                ->badge($currentStats['total'])
                ->badgeColor('gray'),
                
            'pending' => Tab::make('🕐 Tertunda')
                ->badge($currentStats['pending'])
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('✅ Disetujui')
                ->badge($currentStats['approved'])
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui')),
                
            'rejected' => Tab::make('❌ Ditolak')
                ->badge($currentStats['rejected'])
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'ditolak')),
                
            'revision' => Tab::make('📝 Perlu Revisi')
                ->badge($currentStats['revision'])
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'need_revision')),
                
            'today' => Tab::make('📅 Hari Ini')
                ->badge($currentStats['today'])
                ->badgeColor('purple')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today())),
                
            'high_value' => Tab::make('💎 Nilai Tinggi')
                ->badge($currentStats['high_value'])
                ->badgeColor('orange')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nominal', '>', 5000000)),
        ];
    }


    public function getSubheading(): ?string
    {
        $stats = $this->getFinancialStats();
        $activeTab = $this->getActiveTab();
        
        if ($activeTab === 'pengeluaran') {
            $currentStats = $stats['pengeluaran'];
            return "Pengeluaran: {$currentStats['total']} | Pending: {$currentStats['pending']} | Hari Ini: {$currentStats['today']}";
        } else {
            $currentStats = $stats['pendapatan'];
            return "Penerimaan: {$currentStats['total']} | Pending: {$currentStats['pending']} | Hari Ini: {$currentStats['today']}";
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Bendahara\Widgets\FinancialOverviewWidget::class,
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
        $activeTab = $this->getActiveTab();
        $type = $activeTab === 'pengeluaran' ? 'Pengeluaran' : 'Penerimaan';
        return "🏦 Pusat Validasi Keuangan - {$type}";
    }

    private function getFinancialStats(): array
    {
        // Pendapatan stats
        $pendapatanQuery = Pendapatan::whereNotNull('input_by');
        $pendapatanStats = [
            'total' => $pendapatanQuery->count(),
            'pending' => $pendapatanQuery->where('status_validasi', 'pending')->count(),
            'approved' => $pendapatanQuery->where('status_validasi', 'disetujui')->count(),
            'rejected' => $pendapatanQuery->where('status_validasi', 'ditolak')->count(),
            'revision' => $pendapatanQuery->where('status_validasi', 'need_revision')->count(),
            'today' => $pendapatanQuery->whereDate('tanggal', today())->count(),
            'high_value' => $pendapatanQuery->where('nominal', '>', 5000000)->count(),
            'total_value' => $pendapatanQuery->sum('nominal'),
        ];

        // Pengeluaran stats
        $pengeluaranQuery = Pengeluaran::whereNotNull('input_by');
        $pengeluaranStats = [
            'total' => $pengeluaranQuery->count(),
            'pending' => $pengeluaranQuery->where('status_validasi', 'pending')->count(),
            'approved' => $pengeluaranQuery->where('status_validasi', 'disetujui')->count(),
            'rejected' => $pengeluaranQuery->where('status_validasi', 'ditolak')->count(),
            'revision' => $pengeluaranQuery->where('status_validasi', 'need_revision')->count(),
            'today' => $pengeluaranQuery->whereDate('tanggal', today())->count(),
            'high_value' => $pengeluaranQuery->where('nominal', '>', 5000000)->count(),
            'total_value' => $pengeluaranQuery->sum('nominal'),
        ];

        // Combined stats
        $combined = [
            'total_pending' => $pendapatanStats['pending'] + $pengeluaranStats['pending'],
            'total_today' => $pendapatanStats['today'] + $pengeluaranStats['today'],
            'net_cash_flow' => $pendapatanStats['total_value'] - $pengeluaranStats['total_value'],
            'total_high_value' => $pendapatanStats['high_value'] + $pengeluaranStats['high_value'],
        ];

        return [
            'pendapatan' => $pendapatanStats,
            'pengeluaran' => $pengeluaranStats,
            'combined' => $combined,
        ];
    }

    private function performQuickFinancialAction(string $transactionType, string $actionType): void
    {
        try {
            $affected = 0;
            
            $models = match($transactionType) {
                'pendapatan' => [Pendapatan::class],
                'pengeluaran' => [Pengeluaran::class],
                'both' => [Pendapatan::class, Pengeluaran::class],
                default => [Pendapatan::class]
            };

            foreach ($models as $modelClass) {
                switch ($actionType) {
                    case 'approve_low_value':
                        $records = $modelClass::where('status_validasi', 'pending')
                            ->where('nominal', '<', 500000)
                            ->get();
                        
                        foreach ($records as $record) {
                            $record->update([
                                'status_validasi' => 'disetujui',
                                'validasi_by' => auth()->id(),
                                'validasi_at' => now(),
                                'catatan_validasi' => 'Auto-approved: Low value routine transaction'
                            ]);
                            $affected++;
                        }
                        break;
                        
                    case 'approve_routine':
                        $routineCategories = ['konsultasi', 'operasional'];
                        $records = $modelClass::where('status_validasi', 'pending')
                            ->whereIn('kategori', $routineCategories)
                            ->where('nominal', '<', 1000000)
                            ->get();
                        
                        foreach ($records as $record) {
                            $record->update([
                                'status_validasi' => 'disetujui',
                                'validasi_by' => auth()->id(),
                                'validasi_at' => now(),
                                'catatan_validasi' => 'Auto-approved: Routine category transaction'
                            ]);
                            $affected++;
                        }
                        break;
                        
                    case 'flag_high_value':
                        $records = $modelClass::where('status_validasi', 'pending')
                            ->where('nominal', '>', 5000000)
                            ->get();
                        
                        foreach ($records as $record) {
                            $currentComment = $record->catatan_validasi ?? '';
                            $flagNote = '🚩 FLAGGED: High value transaction requires manual review';
                            
                            $record->update([
                                'catatan_validasi' => $currentComment ? "{$currentComment}\n{$flagNote}" : $flagNote,
                            ]);
                            $affected++;
                        }
                        break;

                    case 'categorize_by_amount':
                        $records = $modelClass::where('status_validasi', 'pending')->get();
                        
                        foreach ($records as $record) {
                            $note = match(true) {
                                $record->nominal > 10000000 => '💎 Ultra High Value - Requires C-Level Approval',
                                $record->nominal > 5000000 => '🔶 High Value - Requires Supervisor Review',
                                $record->nominal > 1000000 => '🔸 Medium Value - Standard Review Required',
                                default => '🔹 Standard Value - Fast Track Eligible'
                            };
                            
                            $currentComment = $record->catatan_validasi ?? '';
                            $record->update([
                                'catatan_validasi' => $currentComment ? "{$currentComment}\n{$note}" : $note,
                            ]);
                            $affected++;
                        }
                        break;
                }
            }

            Notification::make()
                ->title('⚡ Quick Action Complete')
                ->body("Action completed successfully. {$affected} records affected.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Quick Action Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function exportFinancialView(string $format, string $scope): void
    {
        // Export functionality placeholder
        $scopeDescription = match($scope) {
            'current_tab' => 'current tab data',
            'both_types' => 'both income and expenses',
            'pending_only' => 'pending validations only',
            default => 'selected data'
        };
        
        Notification::make()
            ->title('📤 Export Started')
            ->body("Exporting {$scopeDescription} to {$format} format. You will be notified when ready.")
            ->info()
            ->send();
    }
}