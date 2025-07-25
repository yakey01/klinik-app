<?php

namespace App\Filament\Bendahara\Resources\JaspelManagementResource\Pages;

use App\Filament\Bendahara\Resources\JaspelManagementResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateJaspelManagement extends CreateRecord
{
    protected static string $resource = JaspelManagementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = Auth::id();
        $data['total_jaspel'] = $data['nominal'];
        
        return $data;
    }
}