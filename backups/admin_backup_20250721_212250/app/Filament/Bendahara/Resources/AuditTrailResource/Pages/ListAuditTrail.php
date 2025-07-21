<?php

namespace App\Filament\Bendahara\Resources\AuditTrailResource\Pages;

use App\Filament\Bendahara\Resources\AuditTrailResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditTrail extends ListRecords
{
    protected static string $resource = AuditTrailResource::class;

    protected static ?string $title = '🔍 Audit Trail';
}