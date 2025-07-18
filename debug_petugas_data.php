<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\User;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Pegawai;

echo "=== DEBUG: PETUGAS TINA DATA ANALYSIS ===\n\n";

// 1. Check for users named Tina
echo "1. USERS WITH NAME 'TINA':\n";
$tinaUsers = User::where('name', 'LIKE', '%Tina%')
    ->orWhere('email', 'LIKE', '%tina%')
    ->with('role')
    ->get();

foreach ($tinaUsers as $user) {
    echo "- ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Role: " . ($user->role ? $user->role->display_name : 'No Role') . "\n";
    echo "  Username: " . ($user->username ? $user->username : 'N/A') . "\n\n";
}

// 2. Check for pegawai named Tina
echo "2. PEGAWAI WITH NAME 'TINA':\n";
$tinaPegawai = Pegawai::where('nama_lengkap', 'LIKE', '%Tina%')->get();

foreach ($tinaPegawai as $pegawai) {
    echo "- ID: {$pegawai->id}\n";
    echo "  Name: {$pegawai->nama_lengkap}\n";
    echo "  Email: {$pegawai->email}\n";
    echo "  Jenis: {$pegawai->jenis_pegawai}\n";
    echo "  User ID: " . ($pegawai->user_id ? $pegawai->user_id : 'N/A') . "\n\n";
}

// 3. Check all users with 'petugas' role
echo "3. ALL PETUGAS USERS:\n";
$petugasUsers = User::whereHas('role', function ($query) {
    $query->where('name', 'petugas');
})->with('role')->get();

foreach ($petugasUsers as $user) {
    echo "- ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Username: " . ($user->username ? $user->username : 'N/A') . "\n\n";
}

// 4. Check pendapatan by petugas users
echo "4. PENDAPATAN INPUT BY PETUGAS:\n";
$pendapatanByPetugas = Pendapatan::whereIn('input_by', $petugasUsers->pluck('id'))
    ->with('inputBy')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($pendapatanByPetugas as $pendapatan) {
    echo "- ID: {$pendapatan->id}\n";
    echo "  Name: {$pendapatan->nama_pendapatan}\n";
    echo "  Nominal: Rp " . number_format($pendapatan->nominal, 0, ',', '.') . "\n";
    echo "  Input By: {$pendapatan->inputBy->name} (ID: {$pendapatan->input_by})\n";
    echo "  Status: {$pendapatan->status_validasi}\n";
    echo "  Date: {$pendapatan->tanggal}\n\n";
}

// 5. Check pengeluaran by petugas users  
echo "5. PENGELUARAN INPUT BY PETUGAS:\n";
$pengeluaranByPetugas = Pengeluaran::whereIn('input_by', $petugasUsers->pluck('id'))
    ->with('inputBy')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($pengeluaranByPetugas as $pengeluaran) {
    echo "- ID: {$pengeluaran->id}\n";
    echo "  Name: {$pengeluaran->nama_pengeluaran}\n";
    echo "  Nominal: Rp " . number_format($pengeluaran->nominal, 0, ',', '.') . "\n";
    echo "  Input By: {$pengeluaran->inputBy->name} (ID: {$pengeluaran->input_by})\n";
    echo "  Status: {$pengeluaran->status_validasi}\n";
    echo "  Date: {$pengeluaran->tanggal}\n\n";
}

// 6. Check dummy data that might appear in bendahara
echo "6. POTENTIAL DUMMY DATA IN BENDAHARA:\n";
echo "Pendapatan with input_by = 1 (Admin):\n";
$dummyPendapatan = Pendapatan::where('input_by', 1)->count();
echo "Count: {$dummyPendapatan}\n\n";

echo "Pengeluaran with input_by = 1 (Admin):\n";
$dummyPengeluaran = Pengeluaran::where('input_by', 1)->count();
echo "Count: {$dummyPengeluaran}\n\n";

// 7. Summary for bendahara validation
echo "7. SUMMARY FOR BENDAHARA VALIDATION:\n";
$pendapatanPending = Pendapatan::where('status_validasi', 'pending')->count();
$pengeluaranPending = Pengeluaran::where('status_validasi', 'pending')->count();

echo "Pendapatan pending validation: {$pendapatanPending}\n";
echo "Pengeluaran pending validation: {$pengeluaranPending}\n";

echo "\n=== END OF ANALYSIS ===\n";