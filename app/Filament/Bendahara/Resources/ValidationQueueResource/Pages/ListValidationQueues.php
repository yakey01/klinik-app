<?php

namespace App\Filament\Bendahara\Resources\ValidationQueueResource\Pages;

use App\Filament\Bendahara\Resources\ValidationQueueResource;
use App\Models\BendaharaValidationQueue;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\IconPosition;

class ListValidationQueues extends ListRecords
{
    protected static string $resource = ValidationQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('🔄 Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->redirect(request()->header('Referer')))
                ->keyBindings(['cmd+r', 'ctrl+r']),
                
            Actions\Action::make('stats')
                ->label('📊 Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('📊 Validation Queue Statistics')
                ->modalContent(view('filament.bendahara.validation-stats'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('🔍 All Requests')
                ->badge(BendaharaValidationQueue::count())
                ->badgeColor('gray'),
                
            'pending' => Tab::make('⏳ Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(BendaharaValidationQueue::where('status', 'pending')->count())
                ->badgeColor('warning'),
                
            'my_assigned' => Tab::make('👤 My Assigned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assigned_to', auth()->id())
                    ->whereIn('status', ['assigned', 'in_review']))
                ->badge(BendaharaValidationQueue::where('assigned_to', auth()->id())
                    ->whereIn('status', ['assigned', 'in_review'])
                    ->count())
                ->badgeColor('primary'),
                
            'tindakan' => Tab::make('🏥 Tindakan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('validation_type', 'tindakan'))
                ->badge(BendaharaValidationQueue::where('validation_type', 'tindakan')->count())
                ->badgeColor('success'),
                
            'pendapatan' => Tab::make('💰 Pendapatan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('validation_type', 'pendapatan_harian'))
                ->badge(BendaharaValidationQueue::where('validation_type', 'pendapatan_harian')->count())
                ->badgeColor('info'),
                
            'urgent' => Tab::make('🚨 Urgent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('priority', 'urgent')
                    ->whereIn('status', ['pending', 'assigned', 'in_review']))
                ->badge(BendaharaValidationQueue::where('priority', 'urgent')
                    ->whereIn('status', ['pending', 'assigned', 'in_review'])
                    ->count())
                ->badgeColor('danger'),
                
            'completed' => Tab::make('✅ Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')
                    ->where('completed_at', '>=', now()->subDays(7)))
                ->badge(BendaharaValidationQueue::where('status', 'completed')
                    ->where('completed_at', '>=', now()->subDays(7))
                    ->count())
                ->badgeColor('success'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery();
    }

    public function getTitle(): string
    {
        return '📋 Validation Queue Management';
    }

    public function getHeading(): string
    {
        return '📋 Validation Queue Management';
    }

    public function getSubheading(): ?string
    {
        $pending = BendaharaValidationQueue::where('status', 'pending')->count();
        $myAssigned = BendaharaValidationQueue::where('assigned_to', auth()->id())
            ->whereIn('status', ['assigned', 'in_review'])
            ->count();
        $urgent = BendaharaValidationQueue::where('priority', 'urgent')
            ->whereIn('status', ['pending', 'assigned', 'in_review'])
            ->count();
            
        $parts = [];
        if ($pending > 0) {
            $parts[] = "⏳ {$pending} pending";
        }
        if ($myAssigned > 0) {
            $parts[] = "👤 {$myAssigned} assigned to you";
        }
        if ($urgent > 0) {
            $parts[] = "🚨 {$urgent} urgent";
        }
        
        return !empty($parts) ? implode(' • ', $parts) : 'All validation requests are processed';
    }
}