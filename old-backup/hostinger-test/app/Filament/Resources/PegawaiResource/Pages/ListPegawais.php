<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Filament\Widgets\PegawaiStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;
    

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Karyawan')
                ->icon('heroicon-m-plus')
                ->color('primary'),
                
            // Action to create user account for staff management
            Actions\Action::make('create_user_account')
                ->label('Buat Akun User')
                ->icon('heroicon-m-user-plus')
                ->color('success')
                ->url(fn() => url('/admin/users/create?source=staff_management'))
                ->tooltip('Buat akun login untuk Petugas, Bendahara, atau Pegawai')
                ->openUrlInNewTab(false)
                ->visible(fn() => auth()->user()?->hasPermissionTo('create_user')),
        ];
    }

    public function getTitle(): string
    {
        return '🧑‍⚕️ Manajemen Pegawai';
    }

    public function getHeading(): string
    {
        return 'Daftar Karyawan';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PegawaiStatsWidget::class,
        ];
    }

}
