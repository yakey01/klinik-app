<?php

namespace App\Filament\Bendahara\Resources\ValidasiJaspelResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJaspelResource;
use App\Models\Jaspel;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditValidasiJaspel extends EditRecord
{
    protected static string $resource = ValidasiJaspelResource::class;

    protected static ?string $title = '✏️ Edit Validasi Jaspel';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            
            Actions\DeleteAction::make()
                ->visible(fn (Jaspel $record): bool => 
                    $record->status_pembayaran === 'pending' && 
                    Auth::user()->can('delete', $record)
                ),

            Actions\Action::make('audit_log')
                ->label('📋 Audit Log')
                ->color('gray')
                ->action(function (Jaspel $record) {
                    // Show audit trail for this record
                    Notification::make()
                        ->title('📋 Audit Log')
                        ->body("Menampilkan audit trail untuk jaspel {$record->user->name}")
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Jaspel Updated')
            ->body('Data validasi jaspel berhasil diperbarui.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Add validation metadata
        $data['validasi_by'] = Auth::id();
        $data['validasi_at'] = now();
        
        // Calculate tax and net amount if approved
        if ($data['status_pembayaran'] === 'approved' && isset($data['total_jaspel'])) {
            $taxRate = 0.05; // 5% tax rate
            $data['tax_amount'] = $data['total_jaspel'] * $taxRate;
            $data['net_amount'] = $data['total_jaspel'] - $data['tax_amount'];
            $data['approved_amount'] = $data['total_jaspel'];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        // Log the update
        activity()
            ->performedOn($record)
            ->causedBy(Auth::user())
            ->withProperties([
                'status' => $record->status_pembayaran,
                'amount' => $record->total_jaspel,
                'notes' => $record->catatan_bendahara,
            ])
            ->log('Jaspel validation updated');

        // Send notification based on status
        $this->sendStatusNotification($record);
    }

    private function sendStatusNotification(Jaspel $record): void
    {
        $status = $record->status_pembayaran;
        $userName = $record->user->name;

        match($status) {
            'approved' => Notification::make()
                ->title('✅ Jaspel Disetujui')
                ->body("Jaspel untuk {$userName} telah disetujui")
                ->success()
                ->sendToDatabase([$record->user_id]),
                
            'rejected' => Notification::make()
                ->title('❌ Jaspel Ditolak')
                ->body("Jaspel untuk {$userName} ditolak. Silakan periksa catatan.")
                ->danger()
                ->sendToDatabase([$record->user_id]),
                
            'paid' => Notification::make()
                ->title('💰 Jaspel Dibayar')
                ->body("Pembayaran jaspel untuk {$userName} telah selesai")
                ->success()
                ->sendToDatabase([$record->user_id]),
                
            default => null,
        };
    }

    public function getSubheading(): ?string
    {
        return 'Edit validation details, status, dan payment information untuk jaspel record.';
    }
}