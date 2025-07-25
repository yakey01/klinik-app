<?php

namespace App\Filament\Resources\AssignmentHistoryResource\Pages;

use App\Filament\Resources\AssignmentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignmentHistory extends EditRecord
{
    protected static string $resource = AssignmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
