<?php

namespace App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;

use App\Events\ExpenseCreated;
use App\Filament\Petugas\Resources\PengeluaranHarianResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePengeluaranHarian extends CreateRecord
{
    protected static string $resource = PengeluaranHarianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status_validasi'] = 'pending';
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Dispatch ExpenseCreated event for Telegram notifications
        event(new ExpenseCreated([
            'amount' => $this->record->nominal ?? 0,
            'description' => $this->record->keterangan ?? $this->record->nama_pengeluaran ?? 'Pengeluaran harian',
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role?->name ?? 'petugas',
            'pengeluaran_id' => $this->record->id,
            'category' => $this->record->kategori ?? 'Harian',
            'shift' => $this->record->shift ?? 'Unknown',
            'tanggal' => $this->record->tanggal_input ?? now(),
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}