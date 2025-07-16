<?php

namespace App\Filament\Bendahara\Resources\BudgetPlanningResource\Pages;

use App\Filament\Bendahara\Resources\BudgetPlanningResource;
use Filament\Resources\Pages\ListRecords;

class ListBudgetPlanning extends ListRecords
{
    protected static string $resource = BudgetPlanningResource::class;

    protected static ?string $title = '🎯 Budget Planning';
}