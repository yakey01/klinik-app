<?php

namespace App\Filament\Widgets;

use App\Models\ReportExecution;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ReportExecutionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Executions';

    protected int | string | array $columnSpan = 'full';

    // protected static ?string $pollingInterval = null; // DISABLED - emergency polling removal

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReportExecution::whereHas('report', function ($query) {
                    $query->where('user_id', Auth::id())
                        ->orWhere('is_public', true);
                })
                ->orderBy('created_at', 'desc')
                ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('report.name')
                    ->label('Report')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('report.category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'financial' => 'success',
                        'operational' => 'primary',
                        'medical' => 'info',
                        'administrative' => 'warning',
                        'security' => 'danger',
                        'performance' => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Executed By')
                    ->limit(20),
                Tables\Columns\TextColumn::make('result_count')
                    ->label('Results')
                    ->numeric()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('execution_time')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) . 'ms' : 'N/A')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('memory_usage')
                    ->label('Memory')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024 / 1024, 2) . 'MB' : 'N/A')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Started'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->since()
                    ->label('Completed')
                    ->placeholder('N/A'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_result')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('View Results')
                    ->modalContent(function (ReportExecution $record) {
                        if ($record->status === 'completed' && $record->result_data) {
                            return view('filament.modals.execution-result', [
                                'execution' => $record,
                                'data' => json_decode($record->result_data, true)
                            ]);
                        }
                        return 'No results available';
                    })
                    ->modalHeading(fn (ReportExecution $record) => 'Results for ' . $record->report->name)
                    ->modalWidth('7xl')
                    ->visible(fn (ReportExecution $record) => $record->status === 'completed' && $record->result_data),
                Tables\Actions\Action::make('view_error')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->tooltip('View Error')
                    ->modalContent(function (ReportExecution $record) {
                        return view('filament.modals.execution-error', [
                            'execution' => $record,
                            'error' => $record->error_message
                        ]);
                    })
                    ->modalHeading(fn (ReportExecution $record) => 'Error for ' . $record->report->name)
                    ->modalWidth('4xl')
                    ->visible(fn (ReportExecution $record) => $record->status === 'failed' && $record->error_message),
            ])
            ->emptyStateHeading('No executions yet')
            ->emptyStateDescription('Execute a report to see results here')
            ->emptyStateIcon('heroicon-o-play');
    }
}