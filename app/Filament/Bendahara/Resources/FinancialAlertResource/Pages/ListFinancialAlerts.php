<?php

namespace App\Filament\Bendahara\Resources\FinancialAlertResource\Pages;

use App\Filament\Bendahara\Resources\FinancialAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListFinancialAlerts extends ListRecords
{
    protected static string $resource = FinancialAlertResource::class;

    protected static ?string $title = '🚨 Financial Alerts';
}