<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Role;
use App\Models\JenisTindakan;
use App\Models\Pegawai;
use App\Models\Dokter;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UsersExport(),
            new RolesExport(),
            new JenisTindakanExport(),
            new PegawaiExport(),
            new DokterExport(),
        ];
    }
}

class UsersExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
{
    public function collection()
    {
        return User::with('role')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->name ?? '',
                'nip' => $user->nip,
                'no_telepon' => $user->no_telepon,
                'tanggal_bergabung' => $user->tanggal_bergabung,
                'is_active' => $user->is_active ? 'Ya' : 'Tidak',
                'created_at' => $user->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Username',
            'Email',
            'Role',
            'NIP',
            'No Telepon',
            'Tanggal Bergabung',
            'Status Aktif',
            'Dibuat Pada',
        ];
    }

    public function title(): string
    {
        return 'Users';
    }
}

class RolesExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
{
    public function collection()
    {
        return Role::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Display Name',
            'Deskripsi',
            'Permissions',
            'Status Aktif',
            'Dibuat Pada',
            'Diperbarui Pada',
        ];
    }

    public function title(): string
    {
        return 'Roles';
    }
}

class JenisTindakanExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
{
    public function collection()
    {
        return JenisTindakan::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Tarif',
            'Jaspel Dokter',
            'Jaspel Paramedis',
            'Jaspel Non-Paramedis',
            'Deskripsi',
            'Status Aktif',
            'Dibuat Pada',
            'Diperbarui Pada',
        ];
    }

    public function title(): string
    {
        return 'Jenis Tindakan';
    }
}

class PegawaiExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
{
    public function collection()
    {
        return Pegawai::with('user')->get()->map(function ($pegawai) {
            return [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'nip' => $pegawai->nip,
                'jabatan' => $pegawai->jabatan,
                'spesialisasi' => $pegawai->spesialisasi,
                'no_telepon' => $pegawai->no_telepon,
                'alamat' => $pegawai->alamat,
                'tanggal_bergabung' => $pegawai->tanggal_bergabung,
                'is_active' => $pegawai->is_active ? 'Ya' : 'Tidak',
                'user_id' => $pegawai->user_id,
                'created_at' => $pegawai->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'NIP',
            'Jabatan',
            'Spesialisasi',
            'No Telepon',
            'Alamat',
            'Tanggal Bergabung',
            'Status Aktif',
            'User ID',
            'Dibuat Pada',
        ];
    }

    public function title(): string
    {
        return 'Pegawai';
    }
}

class DokterExport implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
{
    public function collection()
    {
        return Dokter::with('user')->get()->map(function ($dokter) {
            return [
                'id' => $dokter->id,
                'nama' => $dokter->nama,
                'str_number' => $dokter->str_number,
                'spesialisasi' => $dokter->spesialisasi,
                'no_telepon' => $dokter->no_telepon,
                'alamat' => $dokter->alamat,
                'tanggal_bergabung' => $dokter->tanggal_bergabung,
                'is_active' => $dokter->is_active ? 'Ya' : 'Tidak',
                'user_id' => $dokter->user_id,
                'created_at' => $dokter->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'STR Number',
            'Spesialisasi',
            'No Telepon',
            'Alamat',
            'Tanggal Bergabung',
            'Status Aktif',
            'User ID',
            'Dibuat Pada',
        ];
    }

    public function title(): string
    {
        return 'Dokter';
    }
}