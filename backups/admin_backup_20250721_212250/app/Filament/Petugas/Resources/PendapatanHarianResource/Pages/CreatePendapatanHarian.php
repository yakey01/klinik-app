<?php

namespace App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;

use App\Events\IncomeCreated;
use App\Filament\Petugas\Resources\PendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreatePendapatanHarian extends CreateRecord
{
    protected static string $resource = PendapatanHarianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Dispatch IncomeCreated event for Telegram notifications
        event(new IncomeCreated([
            'amount' => $this->record->nominal ?? 0,
            'description' => $this->record->keterangan ?? $this->record->nama_pendapatan ?? 'Pendapatan harian',
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role?->name ?? 'petugas',
            'pendapatan_id' => $this->record->id,
            'category' => $this->record->kategori ?? 'Harian',
            'shift' => $this->record->shift ?? 'Unknown',
            'tanggal' => $this->record->tanggal_input ?? now(),
        ]));
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Pendapatan berhasil disimpan')
            ->body('Data pendapatan harian telah berhasil ditambahkan.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
