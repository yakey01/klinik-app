<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\ReportExecution;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Services\ReportService;

class RecentReportsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Reports';

    protected int | string | array $columnSpan = 'full';

    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Report::where('user_id', Auth::id())
                    ->orWhere('is_public', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Report::CATEGORY_FINANCIAL => 'success',
                        Report::CATEGORY_OPERATIONAL => 'primary',
                        Report::CATEGORY_MEDICAL => 'info',
                        Report::CATEGORY_ADMINISTRATIVE => 'warning',
                        Report::CATEGORY_SECURITY => 'danger',
                        Report::CATEGORY_PERFORMANCE => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('report_type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Report::TYPE_TABLE => 'Table',
                        Report::TYPE_CHART => 'Chart',
                        Report::TYPE_DASHBOARD => 'Dashboard',
                        Report::TYPE_EXPORT => 'Export',
                        Report::TYPE_KPI => 'KPI',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Report::STATUS_DRAFT => 'gray',
                        Report::STATUS_ACTIVE => 'success',
                        Report::STATUS_INACTIVE => 'warning',
                        Report::STATUS_ARCHIVED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->limit(20),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),
                Tables\Columns\TextColumn::make('executions_count')
                    ->counts('executions')
                    ->label('Runs'),
                Tables\Columns\TextColumn::make('last_generated_at')
                    ->dateTime()
                    ->since()
                    ->label('Last Run'),
            ])
            ->actions([
                Action::make('execute')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->tooltip('Execute Report')
                    ->action(function (Report $record) {
                        $reportService = app(ReportService::class);
                        try {
                            $execution = $reportService->executeReport($record, Auth::user());
                            Notification::make()
                                ->title('Report executed successfully')
                                ->body("Execution completed in {$execution->getFormattedExecutionTime()}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Report execution failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->tooltip('View Report')
                    ->url(fn (Report $record): string => route('filament.admin.resources.reports.view', $record)),
            ])
            ->emptyStateHeading('No reports yet')
            ->emptyStateDescription('Create your first report to get started')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }
}