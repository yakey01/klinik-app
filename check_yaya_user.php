<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING USER 'YAYA' ===\n\n";

// 1. Check in users table
echo "1. CHECKING USERS TABLE:\n";
$user = App\Models\User::where('name', 'LIKE', '%yaya%')
    ->orWhere('email', 'LIKE', '%yaya%')
    ->orWhere('username', 'LIKE', '%yaya%')
    ->first();

if ($user) {
    echo "✅ Found user in users table:\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Username: " . ($user->username ?: 'NULL') . "\n";
    echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "Has password: " . (!empty($user->password) ? 'Yes' : 'No') . "\n";
    echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "Role ID: " . ($user->role_id ?: 'NULL') . "\n";
    if ($user->role) {
        echo "Role (via relation): {$user->role->name} ({$user->role->display_name})\n";
    }
} else {
    echo "❌ User 'yaya' not found in users table\n";
}

// 2. Check in dokter table
echo "\n2. CHECKING DOKTER TABLE:\n";
$dokter = App\Models\Dokter::where('nama_lengkap', 'LIKE', '%yaya%')
    ->orWhere('username', 'LIKE', '%yaya%')
    ->orWhere('nik', 'LIKE', '%yaya%')
    ->first();

if ($dokter) {
    echo "✅ Found dokter record:\n";
    echo "ID: {$dokter->id}\n";
    echo "Nama: {$dokter->nama_lengkap}\n";
    echo "Username: " . ($dokter->username ?: 'NULL') . "\n";
    echo "NIK: {$dokter->nik}\n";
    echo "Email: " . ($dokter->email ?: 'NULL') . "\n";
    echo "Has password: " . (!empty($dokter->password) ? 'Yes' : 'No') . "\n";
    echo "Status akun: {$dokter->status_akun}\n";
    echo "Aktif: " . ($dokter->aktif ? 'Yes' : 'No') . "\n";
    echo "User ID: " . ($dokter->user_id ?: 'NULL') . "\n";
    
    if ($dokter->user_id && $dokter->user) {
        echo "Linked user: {$dokter->user->name} ({$dokter->user->email})\n";
    }
} else {
    echo "❌ Dokter 'yaya' not found in dokter table\n";
}

// 3. Check in pegawai table (just in case)
echo "\n3. CHECKING PEGAWAI TABLE:\n";
$pegawai = App\Models\Pegawai::where('nama_lengkap', 'LIKE', '%yaya%')
    ->orWhere('username', 'LIKE', '%yaya%')
    ->first();

if ($pegawai) {
    echo "✅ Found pegawai record:\n";
    echo "ID: {$pegawai->id}\n";
    echo "Nama: {$pegawai->nama_lengkap}\n";
    echo "Username: " . ($pegawai->username ?: 'NULL') . "\n";
    echo "Jenis: {$pegawai->jenis_pegawai}\n";
    echo "Email: " . ($pegawai->email ?: 'NULL') . "\n";
    echo "Has password: " . (!empty($pegawai->password) ? 'Yes' : 'No') . "\n";
    echo "Status akun: {$pegawai->status_akun}\n";
    echo "Aktif: " . ($pegawai->aktif ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Pegawai 'yaya' not found\n";
}

// 4. Show all users with dokter role
echo "\n4. ALL USERS WITH DOKTER ROLE:\n";
$dokterUsers = App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'dokter');
})->orWhere('role_id', function($query) {
    $query->select('id')->from('roles')->where('name', 'dokter');
})->get();

if ($dokterUsers->count() > 0) {
    foreach ($dokterUsers as $du) {
        echo "- {$du->name} ({$du->email}) - Active: " . ($du->is_active ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "❌ No users found with dokter role\n";
}

// 5. Show all dokter records
echo "\n5. ALL DOKTER RECORDS:\n";
$allDokters = App\Models\Dokter::select('id', 'nama_lengkap', 'username', 'status_akun', 'aktif')
    ->take(5)->get();

foreach ($allDokters as $d) {
    echo "- {$d->nama_lengkap} (username: " . ($d->username ?: 'NULL') . ") - Status: {$d->status_akun}, Aktif: " . ($d->aktif ? 'Yes' : 'No') . "\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";