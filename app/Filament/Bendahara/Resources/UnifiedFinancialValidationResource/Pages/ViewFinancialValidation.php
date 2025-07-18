<?php

namespace App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource\Pages;

use App\Filament\Bendahara\Resources\UnifiedFinancialValidationResource;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Auth;

class ViewFinancialValidation extends ViewRecord
{
    protected static string $resource = UnifiedFinancialValidationResource::class;

    protected function getHeaderActions(): array
    {
        $isIncome = $this->record instanceof Pendapatan;
        $transactionType = $isIncome ? 'Income' : 'Expense';
        
        return [
            Action::make('back')
                ->label('← Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray')
                ->size(ActionSize::Small),
            
            // Quick Validation Actions
            Action::make('quick_approve')
                ->label('⚡ Quick Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->requiresConfirmation()
                ->modalHeading('⚡ Quick Approve ' . $transactionType)
                ->modalDescription('Approve this ' . strtolower($transactionType) . ' transaction quickly without additional comments?')
                ->modalSubmitActionLabel('Approve Now')
                ->action(function () {
                    $this->quickValidate('disetujui');
                }),

            Action::make('approve_with_comment')
                ->label('✅ Approve with Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->form([
                    Forms\Components\Textarea::make('approval_comment')
                        ->label('Approval Comment')
                        ->placeholder('Add your validation notes...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->quickValidate('disetujui', $data['approval_comment'] ?? null);
                }),

            Action::make('reject_with_reason')
                ->label('❌ Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->placeholder('Please provide a clear reason for rejection...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->quickValidate('ditolak', $data['rejection_reason']);
                }),

            Action::make('request_revision')
                ->label('📝 Request Revision')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status_validasi === 'pending')
                ->form([
                    Forms\Components\Textarea::make('revision_notes')
                        ->label('Revision Notes')
                        ->required()
                        ->placeholder('What specific changes are needed?')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->quickValidate('need_revision', $data['revision_notes']);
                }),

            // Review Actions for processed items
            Action::make('revert_to_pending')
                ->label('🔄 Revert to Pending')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status_validasi, ['disetujui', 'ditolak', 'need_revision']))
                ->requiresConfirmation()
                ->modalHeading('🔄 Revert to Pending Status')
                ->modalDescription('This will return the ' . strtolower($transactionType) . ' to pending status for re-validation.')
                ->modalSubmitActionLabel('Revert')
                ->form([
                    Forms\Components\Textarea::make('revert_reason')
                        ->label('Revert Reason')
                        ->required()
                        ->placeholder('Why is this being reverted for re-validation?')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->revertToPending($data['revert_reason']);
                }),

            // Additional Actions
            Action::make('add_note')
                ->label('📝 Add Note')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->form([
                    Forms\Components\Textarea::make('additional_note')
                        ->label('Additional Note')
                        ->required()
                        ->placeholder('Add a note to this validation...')
                        ->rows(3)
                ])
                ->action(function (array $data) {
                    $this->addValidationNote($data['additional_note']);
                }),

            Action::make('view_timeline')
                ->label('📅 View Timeline')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('📅 Validation Timeline')
                ->modalContent(fn () => view('filament.bendahara.financial-validation-timeline', [
                    'record' => $this->record,
                    'timeline' => $this->getValidationTimeline(),
                    'type' => $isIncome ? 'income' : 'expense'
                ]))
                ->modalWidth('3xl'),

            Action::make('financial_analysis')
                ->label('📊 Financial Analysis')
                ->icon('heroicon-o-chart-bar')
                ->color('purple')
                ->modalHeading('📊 Transaction Analysis')
                ->modalContent(fn () => view('filament.bendahara.financial-analysis', [
                    'record' => $this->record,
                    'analysis' => $this->getFinancialAnalysis(),
                    'type' => $isIncome ? 'income' : 'expense'
                ]))
                ->modalWidth('4xl'),

            // Edit Action
            Actions\EditAction::make()
                ->label('✏️ Edit Details')
                ->visible(fn (): bool => Auth::user()->hasRole(['admin', 'bendahara']))
                ->modalWidth('4xl'),
        ];
    }

    public function getTitle(): string
    {
        $type = $this->record instanceof Pendapatan ? 'Income' : 'Expense';
        return $type . ' Validation Details';
    }

    public function getSubheading(): ?string
    {
        $isIncome = $this->record instanceof Pendapatan;
        $transactionName = $isIncome ? $this->record->nama_pendapatan : $this->record->nama_pengeluaran;
        
        $status = match($this->record->status_validasi) {
            'pending' => '🕐 Pending Validation',
            'disetujui' => '✅ Approved',
            'ditolak' => '❌ Rejected',
            'need_revision' => '📝 Needs Revision',
            default => ucfirst($this->record->status_validasi)
        };
        
        return "{$status} | {$transactionName} | Rp " . number_format($this->record->nominal);
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Could add related transactions widget, category analysis, etc.
        ];
    }

    private function quickValidate(string $status, ?string $comment = null): void
    {
        try {
            $this->record->update([
                'status_validasi' => $status,
                'validasi_by' => Auth::id(),
                'validasi_at' => now(),
                'catatan_validasi' => $comment ?? $this->getDefaultComment($status),
            ]);

            $message = match($status) {
                'disetujui' => '✅ Transaction has been approved successfully',
                'ditolak' => '❌ Transaction has been rejected',
                'need_revision' => '📝 Revision request sent successfully',
                default => 'Transaction processed successfully'
            };
            
            Notification::make()
                ->title('Validation Complete')
                ->body($message)
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Validation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function revertToPending(string $reason): void
    {
        try {
            $this->record->update([
                'status_validasi' => 'pending',
                'validasi_by' => null,
                'validasi_at' => null,
                'catatan_validasi' => "🔄 Reverted by " . Auth::user()->name . ": {$reason}",
            ]);

            Notification::make()
                ->title('🔄 Successfully Reverted')
                ->body('Transaction has been returned to pending status')
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Revert Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function addValidationNote(string $note): void
    {
        try {
            $currentComment = $this->record->catatan_validasi ?? '';
            $timestamp = now()->format('d/m/Y H:i');
            $newNote = "📝 Note by " . Auth::user()->name . " ({$timestamp}): {$note}";
            
            $this->record->update([
                'catatan_validasi' => $currentComment ? "{$currentComment}\n{$newNote}" : $newNote,
            ]);

            Notification::make()
                ->title('📝 Note Added')
                ->body('Validation note has been added successfully')
                ->success()
                ->send();

            // Refresh the record
            $this->record->refresh();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Failed to Add Note')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getDefaultComment(string $status): string
    {
        $isIncome = $this->record instanceof Pendapatan;
        $type = $isIncome ? 'income' : 'expense';
        
        return match($status) {
            'disetujui' => "Approved {$type} transaction in detail view",
            'ditolak' => "Rejected {$type} transaction in detail view",
            'need_revision' => "Revision requested for {$type} transaction",
            default => "Processed {$type} transaction in detail view"
        };
    }

    private function getValidationTimeline(): array
    {
        $timeline = [];
        $isIncome = $this->record instanceof Pendapatan;
        $type = $isIncome ? 'income' : 'expense';
        
        // Created
        $timeline[] = [
            'event' => 'Transaction Created',
            'timestamp' => $this->record->created_at,
            'user' => $this->record->inputBy->name ?? 'System',
            'description' => ucfirst($type) . ' transaction was created and submitted for validation',
            'icon' => 'heroicon-o-plus-circle',
            'color' => 'info'
        ];
        
        // Validation events
        if ($this->record->validasi_at) {
            $timeline[] = [
                'event' => match($this->record->status_validasi) {
                    'disetujui' => 'Approved',
                    'ditolak' => 'Rejected',
                    'need_revision' => 'Revision Requested',
                    default => 'Validated'
                },
                'timestamp' => $this->record->validasi_at,
                'user' => $this->record->validasiBy->name ?? 'Unknown',
                'description' => $this->record->catatan_validasi ?? 'No comment provided',
                'icon' => match($this->record->status_validasi) {
                    'disetujui' => 'heroicon-o-check-circle',
                    'ditolak' => 'heroicon-o-x-circle',
                    'need_revision' => 'heroicon-o-pencil-square',
                    default => 'heroicon-o-clock'
                },
                'color' => match($this->record->status_validasi) {
                    'disetujui' => 'success',
                    'ditolak' => 'danger',
                    'need_revision' => 'warning',
                    default => 'info'
                }
            ];
        }
        
        // Sort by timestamp
        usort($timeline, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return $timeline;
    }

    private function getFinancialAnalysis(): array
    {
        $isIncome = $this->record instanceof Pendapatan;
        $modelClass = $isIncome ? Pendapatan::class : Pengeluaran::class;
        
        // Category analysis
        $categoryTotal = $modelClass::where('kategori', $this->record->kategori)
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', $this->record->tanggal->month)
            ->sum('nominal');
            
        $categoryCount = $modelClass::where('kategori', $this->record->kategori)
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', $this->record->tanggal->month)
            ->count();
            
        $categoryAverage = $categoryCount > 0 ? $categoryTotal / $categoryCount : 0;
        
        // Amount analysis
        $amountPercentile = $this->calculateAmountPercentile();
        
        // Frequency analysis
        $inputByUser = $modelClass::where('input_by', $this->record->input_by)
            ->whereMonth('tanggal', $this->record->tanggal->month)
            ->count();
        
        return [
            'category' => [
                'total_this_month' => $categoryTotal,
                'count_this_month' => $categoryCount,
                'average_amount' => $categoryAverage,
                'vs_this_transaction' => $this->record->nominal - $categoryAverage,
            ],
            'amount' => [
                'percentile' => $amountPercentile,
                'is_outlier' => $amountPercentile > 95 || $amountPercentile < 5,
                'amount_category' => $this->categorizeAmount(),
            ],
            'frequency' => [
                'by_user_this_month' => $inputByUser,
                'user_is_frequent' => $inputByUser > 10,
            ],
            'validation' => [
                'risk_level' => $this->calculateRiskLevel(),
                'requires_attention' => $this->requiresSpecialAttention(),
            ]
        ];
    }
    
    private function calculateAmountPercentile(): float
    {
        $isIncome = $this->record instanceof Pendapatan;
        $modelClass = $isIncome ? Pendapatan::class : Pengeluaran::class;
        
        $smallerCount = $modelClass::where('nominal', '<', $this->record->nominal)->count();
        $totalCount = $modelClass::count();
        
        return $totalCount > 0 ? ($smallerCount / $totalCount) * 100 : 50;
    }
    
    private function categorizeAmount(): string
    {
        return match(true) {
            $this->record->nominal > 10000000 => 'Ultra High Value',
            $this->record->nominal > 5000000 => 'High Value',
            $this->record->nominal > 1000000 => 'Medium Value',
            $this->record->nominal > 100000 => 'Standard Value',
            default => 'Low Value'
        };
    }
    
    private function calculateRiskLevel(): string
    {
        $score = 0;
        
        // Amount-based risk
        if ($this->record->nominal > 10000000) $score += 3;
        elseif ($this->record->nominal > 5000000) $score += 2;
        elseif ($this->record->nominal > 1000000) $score += 1;
        
        // Category-based risk
        $highRiskCategories = ['lainnya', 'infrastruktur'];
        if (in_array($this->record->kategori, $highRiskCategories)) $score += 1;
        
        // Frequency-based risk
        $isIncome = $this->record instanceof Pendapatan;
        $modelClass = $isIncome ? Pendapatan::class : Pengeluaran::class;
        $recentSimilar = $modelClass::where('input_by', $this->record->input_by)
            ->where('kategori', $this->record->kategori)
            ->where('tanggal', '>=', now()->subDays(7))
            ->count();
            
        if ($recentSimilar > 5) $score += 1;
        
        return match(true) {
            $score >= 4 => 'High Risk',
            $score >= 2 => 'Medium Risk',
            default => 'Low Risk'
        };
    }
    
    private function requiresSpecialAttention(): bool
    {
        // Ultra high value transactions
        if ($this->record->nominal > 10000000) return true;
        
        // Unusual category combinations
        $isIncome = $this->record instanceof Pendapatan;
        if ($isIncome && $this->record->kategori === 'lainnya' && $this->record->nominal > 1000000) return true;
        if (!$isIncome && $this->record->kategori === 'infrastruktur' && $this->record->nominal > 5000000) return true;
        
        // Weekend/holiday transactions
        if ($this->record->tanggal->isWeekend()) return true;
        
        return false;
    }
}