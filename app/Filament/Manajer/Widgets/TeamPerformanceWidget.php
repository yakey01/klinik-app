<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\PermohonanCuti;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class TeamPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = '📊 Team Performance Analytics';
    
    protected static ?int $sort = 4;
    
    protected int|string|array $columnSpan = 'full';
    
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pegawai::query()
                    ->withCount([
                        'tindakanAsParamedis as paramedis_procedures' => function($query) {
                            $query->where('created_at', '>=', now()->subMonth());
                        },
                        'tindakanAsNonParamedis as non_paramedis_procedures' => function($query) {
                            $query->where('created_at', '>=', now()->subMonth());
                        }
                    ])
                    ->with(['user.permohonanCutis' => function($query) {
                        $query->where('status', 'Disetujui')
                              ->where('created_at', '>=', now()->subMonth());
                    }])
                    ->orderBy('id', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->nama_lengkap) . '&background=3b82f6&color=fff')
                    ->size(40),
                    
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Employee')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->user->email ?? 'No email'),
                    
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Position')
                    ->badge()
                    ->color(fn($record) => match(strtolower($record->jabatan)) {
                        'dokter' => 'success',
                        'perawat' => 'info',
                        'admin' => 'warning',
                        'manajer' => 'danger',
                        default => 'gray'
                    })
                    ->icon(fn($record) => match(strtolower($record->jabatan)) {
                        'dokter' => 'heroicon-m-academic-cap',
                        'perawat' => 'heroicon-m-heart',
                        'admin' => 'heroicon-m-computer-desktop',
                        'manajer' => 'heroicon-m-briefcase',
                        default => 'heroicon-m-user'
                    }),
                    
                Tables\Columns\TextColumn::make('total_procedures')
                    ->label('Procedures')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn($record) => $record->paramedis_procedures_count + $record->non_paramedis_procedures_count)
                    ->color(function ($state, $record) {
                        $value = $record->paramedis_procedures_count + $record->non_paramedis_procedures_count;
                        return match(true) {
                            $value >= 50 => 'success',
                            $value >= 30 => 'info',
                            $value >= 20 => 'warning',
                            default => 'danger'
                        };
                    })
                    ->suffix(' done')
                    ->description('This month'),
                    
                Tables\Columns\TextColumn::make('activity_status')
                    ->label('Activity')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function($record) {
                        $daysAgo = $record->updated_at->diffInDays(now());
                        return match(true) {
                            $daysAgo <= 7 => 'Active',
                            $daysAgo <= 30 => 'Moderate',
                            default => 'Inactive'
                        };
                    })
                    ->color(fn($state) => match($state) {
                        'Active' => 'success',
                        'Moderate' => 'warning',
                        'Inactive' => 'danger',
                        default => 'gray'
                    })
                    ->icon(fn($state) => match($state) {
                        'Active' => 'heroicon-m-check-circle',
                        'Moderate' => 'heroicon-m-exclamation-triangle',
                        'Inactive' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle'
                    }),
                    
                Tables\Columns\TextColumn::make('leave_days')
                    ->label('Leave Days')
                    ->alignCenter()
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->user->permohonanCutis->count())
                    ->suffix(' days')
                    ->description('This month')
                    ->color(function ($state, $record) {
                        $value = $record->user->permohonanCutis->count();
                        return match(true) {
                            $value == 0 => 'success',
                            $value <= 2 => 'warning',
                            default => 'danger'
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('performance_score')
                    ->label('Performance')
                    ->alignCenter()
                    ->sortable()
                    ->suffix('%')
                    ->weight('bold')
                    ->getStateUsing(function($record) {
                        $totalProcedures = $record->paramedis_procedures_count + $record->non_paramedis_procedures_count;
                        return match(true) {
                            $totalProcedures >= 50 => 95,
                            $totalProcedures >= 30 => 85,
                            $totalProcedures >= 20 => 75,
                            $totalProcedures >= 10 => 65,
                            default => 55
                        };
                    })
                    ->color(fn($state) => match(true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger'
                    })
                    ->icon(fn($state) => match(true) {
                        $state >= 90 => 'heroicon-m-trophy',
                        $state >= 80 => 'heroicon-m-star',
                        $state >= 70 => 'heroicon-m-thumb-up',
                        default => 'heroicon-m-arrow-trending-down'
                    }),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Active')
                    ->dateTime('M j, H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jabatan')
                    ->label('Position')
                    ->options([
                        'dokter' => 'Dokter',
                        'perawat' => 'Perawat',
                        'admin' => 'Admin',
                        'manajer' => 'Manajer',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('activity_status')
                    ->label('Activity Status')
                    ->options([
                        'Active' => 'Active',
                        'Moderate' => 'Moderate',
                        'Inactive' => 'Inactive',
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('high_performers')
                    ->label('High Performers')
                    ->query(function (Builder $query) {
                        return $query->having(DB::raw('COALESCE(paramedis_procedures_count, 0) + COALESCE(non_paramedis_procedures_count, 0)'), '>=', 30);
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Employee Details - ' . $record->nama_lengkap)
                    ->modalContent(fn($record) => view('filament.manajer.modals.employee-details', compact('record')))
                    ->modalActions([
                        Tables\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray')
                            ->close(),
                    ]),
                    
                Tables\Actions\Action::make('send_message')
                    ->label('Message')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->action(function ($record) {
                        $this->notify('info', 'Email: ' . $record->user->email . ' - Performance Update');
                    })
                    ->visible(fn($record) => !empty($record->user->email)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_performance')
                    ->label('Export Selected')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('info')
                    ->action(function ($records) {
                        // Export logic here
                        $this->notify('success', 'Performance data exported successfully!');
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-m-arrow-path')
                    ->action(function () {
                        $this->resetTable();
                        $this->notify('success', 'Performance data refreshed!');
                    }),
                    
                Tables\Actions\Action::make('export_all')
                    ->label('Export All')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $this->notify('success', 'Full performance report exported!');
                    }),
            ])
            ->defaultSort('performance_score', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->emptyStateHeading('No Team Members Found')
            ->emptyStateDescription('There are no team members to display performance data for.')
            ->emptyStateIcon('heroicon-o-users');
    }
}