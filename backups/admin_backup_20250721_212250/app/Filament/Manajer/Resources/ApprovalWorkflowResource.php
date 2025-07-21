<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    
    protected static ?string $navigationLabel = 'Approval Workflows';
    
    protected static ?string $navigationGroup = '⚡ Workflow Management';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tindakan.pasien.nama')
                    ->label('Patient')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),

                Tables\Columns\TextColumn::make('workflow_priority')
                    ->label('Priority')
                    ->state(function (Pendapatan $record): string {
                        $amount = $record->nominal;
                        return match (true) {
                            $amount >= 1000000 => 'High',
                            $amount >= 500000 => 'Medium',
                            default => 'Low',
                        };
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'High' => 'danger',
                            'Medium' => 'warning',
                            'Low' => 'success',
                        };
                    }),

                Tables\Columns\TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->state(function (Pendapatan $record): string {
                        if ($record->status_validasi !== 'pending') {
                            return 'N/A';
                        }
                        $days = now()->diffInDays($record->created_at);
                        return $days . ' days';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'N/A') return 'gray';
                        $days = (int) str_replace(' days', '', $state);
                        return match (true) {
                            $days >= 7 => 'danger',
                            $days >= 3 => 'warning',
                            default => 'success',
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Approval Status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'disetujui' => 'Approved',
                        'ditolak' => 'Rejected',
                    ]),

                Tables\Filters\Filter::make('high_value')
                    ->label('High Value (>1M)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('nominal', '>=', 1000000)
                    ),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue (>7 days)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status_validasi', 'pending')
                              ->where('created_at', '<=', now()->subDays(7))
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Revenue Item')
                    ->modalDescription('Are you sure you want to approve this revenue item?')
                    ->action(function (Pendapatan $record) {
                        $record->update([
                            'status_validasi' => 'disetujui',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);
                        session()->flash('success', 'Revenue item approved successfully');
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Revenue Item')
                    ->modalDescription('Are you sure you want to reject this revenue item?')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, Pendapatan $record) {
                        $record->update([
                            'status_validasi' => 'ditolak',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                        ]);
                        session()->flash('success', 'Revenue item rejected successfully');
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('bulk_approve')
                        ->label('Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Approve Revenue Items')
                        ->modalDescription('Are you sure you want to approve all selected revenue items?')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'disetujui',
                                        'validated_by' => auth()->id(),
                                        'validated_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            session()->flash('success', "Successfully approved {$count} revenue items");
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
            // ->poll() // DISABLED - emergency polling removal
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['tindakan.pasien'])
            ->where('status_validasi', '!=', null);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getEloquentQuery()
            ->where('status_validasi', 'pending')
            ->count();
            
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\ApprovalWorkflowResource\Pages\ListApprovalWorkflows::route('/'),
        ];
    }
}