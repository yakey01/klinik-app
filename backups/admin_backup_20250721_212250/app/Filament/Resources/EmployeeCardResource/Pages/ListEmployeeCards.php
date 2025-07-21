<?php

namespace App\Filament\Resources\EmployeeCardResource\Pages;

use App\Filament\Resources\EmployeeCardResource;
use App\Models\EmployeeCard;
use App\Models\Pegawai;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployeeCards extends ListRecords
{
    protected static string $resource = EmployeeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('🆕 Buat Kartu Baru')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('bulk_generate')
                ->label('🚀 Generate Semua Pegawai')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Kartu untuk Semua Pegawai')
                ->modalDescription('Apakah Anda yakin ingin membuat kartu untuk semua pegawai yang belum memiliki kartu?')
                ->action(function () {
                    $pegawaiWithoutCards = Pegawai::whereDoesntHave('employeeCard')
                        ->where('aktif', true)
                        ->get();
                    
                    $count = 0;
                    $service = app(\App\Services\CardGenerationService::class);
                    
                    foreach ($pegawaiWithoutCards as $pegawai) {
                        $user = \App\Models\User::where('nip', $pegawai->nik)->first();
                        
                        $card = EmployeeCard::create([
                            'pegawai_id' => $pegawai->id,
                            'user_id' => $user?->id,
                            'card_number' => EmployeeCard::generateCardNumber(),
                            'card_type' => 'standard',
                            'design_template' => 'default',
                            'employee_name' => $pegawai->nama_lengkap,
                            'employee_id' => $pegawai->nik,
                            'position' => $pegawai->jabatan,
                            'department' => $pegawai->jenis_pegawai,
                            'role_name' => $user?->role?->display_name,
                            'join_date' => $user?->tanggal_bergabung,
                            'photo_path' => $pegawai->foto,
                            'issued_date' => now()->toDateString(),
                            'is_active' => true,
                            'created_by' => auth()->id(),
                        ]);
                        
                        $result = $service->generateCard($card);
                        if ($result['success']) {
                            $card->update([
                                'pdf_path' => $result['pdf_path'],
                                'generated_at' => now(),
                            ]);
                            $count++;
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title("🎉 Berhasil generate {$count} kartu!")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(EmployeeCard::count()),
            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(EmployeeCard::where('is_active', true)->count())
                ->badgeColor('success'),
            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query->expired())
                ->badge(EmployeeCard::expired()->count())
                ->badgeColor('danger'),
            'standard' => Tab::make('Standard')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('card_type', 'standard'))
                ->badge(EmployeeCard::where('card_type', 'standard')->count()),
            'visitor' => Tab::make('Visitor')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('card_type', 'visitor'))
                ->badge(EmployeeCard::where('card_type', 'visitor')->count()),
        ];
    }
}
