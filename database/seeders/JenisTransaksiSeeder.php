<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JenisTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisTransaksi = [
            // Pendapatan
            ['nama' => 'Konsultasi Umum', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Biaya konsultasi dokter umum'],
            ['nama' => 'Konsultasi Spesialis', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Biaya konsultasi dokter spesialis'],
            ['nama' => 'Pemeriksaan Lab', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Biaya pemeriksaan laboratorium'],
            ['nama' => 'Obat-obatan', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Penjualan obat'],
            ['nama' => 'Tindakan Medis', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Biaya tindakan medis'],
            ['nama' => 'Administrasi', 'kategori' => 'Pendapatan', 'is_aktif' => true, 'deskripsi' => 'Biaya administrasi'],
            
            // Pengeluaran
            ['nama' => 'Gaji Karyawan', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Gaji bulanan karyawan'],
            ['nama' => 'Listrik', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Biaya listrik bulanan'],
            ['nama' => 'Air', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Biaya air bulanan'],
            ['nama' => 'Telepon/Internet', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Biaya komunikasi'],
            ['nama' => 'Pembelian Obat', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Pembelian stok obat'],
            ['nama' => 'Peralatan Medis', 'kategori' => 'Pengeluaran', 'is_aktif' => true, 'deskripsi' => 'Pembelian/perawatan alat medis'],
        ];

        foreach ($jenisTransaksi as $jenis) {
            \App\Models\JenisTransaksi::create($jenis);
        }
    }
}
