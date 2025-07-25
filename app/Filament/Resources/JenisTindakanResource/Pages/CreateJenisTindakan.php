<?php

namespace App\Filament\Resources\JenisTindakanResource\Pages;

use App\Filament\Resources\JenisTindakanResource;
use App\Services\AutoCodeGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateJenisTindakan extends CreateRecord
{
    protected static string $resource = JenisTindakanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(): void
    {
        parent::mount();
        
        // Generate and set the auto-generated code when form is mounted
        $this->form->fill([
            'kode' => AutoCodeGeneratorService::generateJenisTindakanCode(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Double-check and regenerate code if empty or conflicting
        if (empty($data['kode'])) {
            $data['kode'] = AutoCodeGeneratorService::generateJenisTindakanCode();
        }
        
        $data['kode'] = strtoupper($data['kode']);
        
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // Final safety check - regenerate code if it exists
            $existingRecord = $this->getModel()::where('kode', $data['kode'])->first();
            if ($existingRecord) {
                $data['kode'] = AutoCodeGeneratorService::generateJenisTindakanCode();
            }
            
            return parent::handleRecordCreation($data);
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jenis tindakan berhasil ditambahkan!';
    }
}