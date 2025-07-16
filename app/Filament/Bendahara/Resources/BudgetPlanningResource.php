<?php

namespace App\Filament\Bendahara\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class BudgetPlanningResource extends Resource
{
    protected static ?string $model = null; // This is a utility resource for budget planning

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Manajemen Jaspel';

    protected static ?string $navigationLabel = 'Budget Planning';

    protected static ?string $modelLabel = 'Budget Planning';

    protected static ?string $pluralModelLabel = 'Budget Planning';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori Budget')
                    ->searchable(),
                Tables\Columns\TextColumn::make('planned_amount')
                    ->label('Rencana')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('actual_amount')
                    ->label('Realisasi')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('variance')
                    ->label('Variance')
                    ->money('IDR'),
            ])
            ->headerActions([
                Action::make('budget_overview')
                    ->label('📊 Budget Overview')
                    ->color('info')
                    ->action(function () {
                        Notification::make()
                            ->title('📊 Budget Overview')
                            ->body('Budget planning dashboard coming soon!')
                            ->info()
                            ->send();
                    }),
            ]);
    }

    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => BudgetPlanningResource\Pages\ListBudgetPlanning::route('/'),
        ];
    }
}