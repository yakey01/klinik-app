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
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ListFinancialValidations extends ListRecords
{
    protected static string $resource = UnifiedFinancialValidationResource::class;

    protected $activeFinancialTab = 'pendapatan'; // Track which financial model is active

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switch_to_pendapatan')
                ->label('💰 View Income')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => static::getResource()::getUrl('index', ['activeTab' => 'pendapatan']))
                ->visible(fn () => request()->get('activeTab', 'pendapatan') !== 'pendapatan'),
                
            Action::make('switch_to_pengeluaran')
                ->label('💸 View Expenses')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->url(fn () => static::getResource()::getUrl('index', ['activeTab' => 'pengeluaran']))
                ->visible(fn () => request()->get('activeTab', 'pendapatan') !== 'pengeluaran'),
                
            Action::make('financial_statistics')
                ->label('📊 Financial Statistics')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->modalHeading('📊 Financial Validation Statistics')
                ->modalContent(view('filament.bendahara.financial-validation-stats', [
                    'stats' => $this->getFinancialStats()
                ]))
                ->modalWidth('4xl'),
                
            Action::make('quick_actions')
                ->label('⚡ Quick Actions')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->modalHeading('⚡ Quick Financial Validation Actions')
                ->modalDescription('Perform batch operations on pending validations')
                ->form([
                    Forms\Components\Select::make('transaction_type')
                        ->label('Transaction Type')
                        ->options([
                            'pendapatan' => 'Pendapatan (Income)',
                            'pengeluaran' => 'Pengeluaran (Expenses)',
                            'both' => 'Both Types',
                        ])
                        ->default($this->activeFinancialTab)
                        ->required(),
                        
                    Forms\Components\Select::make('action_type')
                        ->label('Action Type')
                        ->options([
                            'approve_low_value' => 'Auto-approve all < 500K',
                            'approve_routine' => 'Auto-approve routine transactions',
                            'flag_high_value' => 'Flag high value items (>5M)',
                            'categorize_by_amount' => 'Auto-categorize by amount ranges',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performQuickFinancialAction($data['transaction_type'], $data['action_type']);
                }),
                
            Action::make('export_current_view')
                ->label('📤 Export Current View')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('export_format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                            'pdf' => 'Financial Report PDF'
                        ])
                        ->default('xlsx')
                        ->required(),
                        
                    Forms\Components\Select::make('export_scope')
                        ->label('Export Scope')
                        ->options([
                            'current_tab' => 'Current Tab Only',
                            'both_types' => 'Both Income & Expenses',
                            'pending_only' => 'Pending Validations Only',
                        ])
                        ->default('current_tab')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->exportFinancialView($data['export_format'], $data['export_scope']);
                }),
                
            Action::make('refresh')
                ->label('🔄 Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => redirect(request()->header('Referer'))),
        ];
    }

    public function getTabs(): array
    {
        $stats = $this->getFinancialStats();
        $activeTab = request()->get('activeTab', 'pendapatan');
        $currentStats = $activeTab === 'pengeluaran' ? $stats['pengeluaran'] : $stats['pendapatan'];
        
        return [
            // Status-based filtering tabs
            'all' => Tab::make('All Records')
                ->badge($currentStats['total'])
                ->badgeColor('gray'),
                
            'pending' => Tab::make('🕐 Pending')
                ->badge($currentStats['pending'])
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('✅ Approved')
                ->badge($currentStats['approved'])
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'disetujui')),
                
            'rejected' => Tab::make('❌ Rejected')
                ->badge($currentStats['rejected'])
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'ditolak')),
                
            'revision' => Tab::make('📝 Need Revision')
                ->badge($currentStats['revision'])
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'need_revision')),
                
            'today' => Tab::make('📅 Today')
                ->badge($currentStats['today'])
                ->badgeColor('purple')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today())),
                
            'high_value' => Tab::make('💎 High Value')
                ->badge($currentStats['high_value'])
                ->badgeColor('orange')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nominal', '>', 5000000)),
        ];
    }


    public function getSubheading(): ?string
    {
        $stats = $this->getFinancialStats();
        $activeTab = request()->get('activeTab', 'pendapatan');
        
        if ($activeTab === 'pengeluaran') {
            $currentStats = $stats['pengeluaran'];
            return "Expenses: {$currentStats['total']} | Pending: {$currentStats['pending']} | Today: {$currentStats['today']}";
        } else {
            $currentStats = $stats['pendapatan'];
            return "Income: {$currentStats['total']} | Pending: {$currentStats['pending']} | Today: {$currentStats['today']}";
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Bendahara\Widgets\FinancialValidationMetricsWidget::class,
        ];
    }

    // Override the table query to handle model switching
    protected function getTableQuery(): Builder
    {
        $activeTab = request()->get('activeTab', 'pendapatan');
        
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
        $activeTab = request()->get('activeTab', 'pendapatan');
        $type = $activeTab === 'pengeluaran' ? 'Expense' : 'Income';
        return "🏦 Financial Validation Center - {$type}";
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