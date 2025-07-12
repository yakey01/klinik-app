<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeCard extends CreateRecord
{
    protected static string $resource = EmployeeCardResource::class;
}
