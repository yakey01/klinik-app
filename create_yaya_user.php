<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CREATING USER 'YAYA' FOR DOKTER TESTING ===\n\n";

// 1. Create or find dokter role
$dokterRole = App\Models\Role::firstOrCreate(
    ['name' => 'dokter'],
    ['display_name' => 'Dokter', 'permissions' => ['basic_access', 'medical_procedures']]
);

echo "✅ Dokter role ready: {$dokterRole->name} (ID: {$dokterRole->id})\n";

// 2. Check if yaya user already exists
$existingUser = App\Models\User::where('email', 'yaya@dokter.com')
    ->orWhere('username', 'yaya')
    ->first();

if ($existingUser) {
    echo "⚠️ User with yaya credentials already exists: {$existingUser->name}\n";
    echo "Updating existing user...\n";
    
    $user = $existingUser;
    $user->update([
        'name' => 'Dr. Yaya',
        'username' => 'yaya',
        'email' => 'yaya@dokter.com',
        'password' => Hash::make('yaya123'),
        'role_id' => $dokterRole->id,
        'is_active' => true,
    ]);
} else {
    echo "Creating new user 'yaya'...\n";
    
    // 3. Create user yaya
    $user = App\Models\User::create([
        'name' => 'Dr. Yaya',
        'username' => 'yaya',
        'email' => 'yaya@dokter.com',
        'password' => Hash::make('yaya123'),
        'role_id' => $dokterRole->id,
        'nip' => 'DOK001',
        'no_telepon' => '081234567890',
        'tanggal_bergabung' => now(),
        'is_active' => true,
    ]);
}

// 4. Assign dokter role using Spatie permissions
if (!$user->hasRole('dokter')) {
    $user->assignRole('dokter');
    echo "✅ Assigned dokter role via Spatie\n";
}

// 5. Create corresponding dokter record
$dokter = App\Models\Dokter::firstOrCreate(
    ['nik' => 'DOK001'],
    [
        'nama_lengkap' => 'Dr. Yaya',
        'username' => 'yaya',
        'email' => 'yaya@dokter.com',
        'password' => Hash::make('yaya123'),
        'jabatan' => 'Dokter Umum',
        'spesialisasi' => 'Dokter Umum',
        'no_telepon' => '081234567890',
        'alamat' => 'Jakarta',
        'tanggal_lahir' => '1985-01-01',
        'jenis_kelamin' => 'L',
        'status_akun' => 'Aktif',
        'aktif' => true,
        'user_id' => $user->id,
    ]
);

echo "✅ Dokter record created/updated: {$dokter->nama_lengkap} (ID: {$dokter->id})\n";

// 6. Test password
$passwordTest = Hash::check('yaya123', $user->password);
echo "✅ Password test: " . ($passwordTest ? 'PASS' : 'FAIL') . "\n";

// 7. Test role assignment
echo "✅ Role via role_id: " . ($user->role ? $user->role->name : 'NULL') . "\n";
echo "✅ Role via Spatie: " . $user->roles->pluck('name')->implode(', ') . "\n";
echo "✅ Has dokter role: " . ($user->hasRole('dokter') ? 'Yes' : 'No') . "\n";

// 8. Test findForAuth
$foundUser = App\Models\User::findForAuth('yaya');
echo "✅ findForAuth test: " . ($foundUser ? "Found {$foundUser->name}" : 'Not found') . "\n";

$foundUserEmail = App\Models\User::findForAuth('yaya@dokter.com');
echo "✅ findForAuth email test: " . ($foundUserEmail ? "Found {$foundUserEmail->name}" : 'Not found') . "\n";

echo "\n=== LOGIN CREDENTIALS FOR YAYA ===\n";
echo "Username: yaya\n";
echo "Email: yaya@dokter.com\n";
echo "Password: yaya123\n";
echo "Expected redirect: /dokter\n";

echo "\n=== USER VERIFICATION ===\n";
$user->refresh();
echo "User ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "Username: {$user->username}\n";
echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
echo "Role ID: {$user->role_id}\n";
echo "Has password: " . (!empty($user->password) ? 'Yes' : 'No') . "\n";

echo "\n✅ User 'yaya' is ready for dokter dashboard testing!\n";