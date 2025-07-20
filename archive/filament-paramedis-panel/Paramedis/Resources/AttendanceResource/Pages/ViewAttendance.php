<?php

namespace App\Filament\Paramedis\Resources\AttendanceResource\Pages;

use App\Filament\Paramedis\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions for view page - paramedis can only view their own records
        ];
    }
}