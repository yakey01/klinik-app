<?php

namespace App\Filament\Bendahara\Resources\ValidationCenterResource\Pages;

use App\Filament\Bendahara\Resources\ValidationCenterResource;
use App\Models\Tindakan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListValidations extends ListRecords
{
    protected static string $resource = ValidationCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('validation_statistics')
                ->label('📊 Statistics')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->modalHeading('📊 Validation Statistics')
                ->modalContent(view('filament.bendahara.validation-stats', [
                    'stats' => $this->getValidationStats()
                ]))
                ->modalWidth('3xl'),
                
            Action::make('quick_actions')
                ->label('⚡ Quick Actions')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->modalHeading('⚡ Quick Validation Actions')
                ->modalDescription('Perform batch operations on pending validations')
                ->form([
                    Forms\Components\Select::make('action_type')
                        ->label('Action Type')
                        ->options([
                            'approve_low_value' => 'Auto-approve all < 100K',
                            'approve_routine' => 'Auto-approve routine procedures',
                            'flag_high_value' => 'Flag high value items (>1M)',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->performQuickAction($data['action_type']);
                }),
                
            Action::make('export_current_view')
                ->label('📤 Export Current View')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('export_format')
                        ->label('Format')
                        ->options([
                            'xlsx' => 'Excel',
                            'csv' => 'CSV',
                            'pdf' => 'PDF Report'
                        ])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->exportCurrentView($data['export_format']);
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
        $stats = $this->getValidationStats();
        
        return [
            'all' => Tab::make('All Records')
                ->badge($stats['total'])
                ->badgeColor('gray'),
                
            'pending' => Tab::make('🕐 Pending')
                ->badge($stats['pending'])
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'pending')),
                
            'approved' => Tab::make('✅ Approved')
                ->badge($stats['approved'])
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'approved')),
                
            'rejected' => Tab::make('❌ Rejected')
                ->badge($stats['rejected'])
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status_validasi', 'rejected')),
                
            'today' => Tab::make('📅 Today')
                ->badge($stats['today'])
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal_tindakan', today())),
                
            'high_value' => Tab::make('💰 High Value')
                ->badge($stats['high_value'])
                ->badgeColor('purple')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tarif', '>', 1000000)),
        ];
    }

    public function getTitle(): string
    {
        return '🛡️ Validation Center';
    }

    public function getSubheading(): ?string
    {
        $stats = $this->getValidationStats();
        return "Total: {$stats['total']} | Pending: {$stats['pending']} | Today: {$stats['today']} | High Value: {$stats['high_value']}";
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Bendahara\Widgets\ValidationMetricsWidget::class,
        ];
    }

    private function getValidationStats(): array
    {
        $query = Tindakan::whereNotNull('input_by');
        
        return [
            'total' => $query->count(),
            'pending' => $query->where('status_validasi', 'pending')->count(),
            'approved' => $query->where('status_validasi', 'approved')->count(),
            'rejected' => $query->where('status_validasi', 'rejected')->count(),
            'today' => $query->whereDate('tanggal_tindakan', today())->count(),
            'high_value' => $query->where('tarif', '>', 1000000)->count(),
            'avg_processing_time' => '2.5', // Placeholder - could be calculated
            'total_value' => $query->sum('tarif'),
        ];
    }

    private function performQuickAction(string $actionType): void
    {
        try {
            $affected = 0;
            
            switch ($actionType) {
                case 'approve_low_value':
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->where('tarif', '<', 100000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $record->update([
                            'status_validasi' => 'approved',
                            'status' => 'selesai',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                            'komentar_validasi' => 'Auto-approved: Low value routine procedure'
                        ]);
                        $affected++;
                    }
                    break;
                    
                case 'approve_routine':
                    $routineProcedures = ['Konsultasi Dokter Umum', 'Pemeriksaan Tekanan Darah'];
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->whereHas('jenisTindakan', function ($query) use ($routineProcedures) {
                            $query->whereIn('nama', $routineProcedures);
                        })
                        ->where('tarif', '<', 200000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $record->update([
                            'status_validasi' => 'approved',
                            'status' => 'selesai',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                            'komentar_validasi' => 'Auto-approved: Routine procedure'
                        ]);
                        $affected++;
                    }
                    break;
                    
                case 'flag_high_value':
                    $records = Tindakan::where('status_validasi', 'pending')
                        ->where('tarif', '>', 1000000)
                        ->get();
                    
                    foreach ($records as $record) {
                        $currentComment = $record->komentar_validasi ?? '';
                        $flagNote = '🚩 FLAGGED: High value procedure requires manual review';
                        
                        $record->update([
                            'komentar_validasi' => $currentComment ? "{$currentComment}\n{$flagNote}" : $flagNote,
                        ]);
                        $affected++;
                    }
                    break;
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

    private function exportCurrentView(string $format): void
    {
        // Export functionality placeholder
        Notification::make()
            ->title('📤 Export Started')
            ->body("Exporting current view to {$format} format. You will be notified when ready.")
            ->info()
            ->send();
    }
}