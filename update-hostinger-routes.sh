#!/bin/bash

# Update routes file on Hostinger

echo "🔧 Updating routes/web.php on Hostinger..."
echo "=========================================="

HOST="153.92.8.132"
PORT="65002"
USER="u454362045"
PASS="LaTahzan@01"

# Create the updated route code
cat > /tmp/updated_route.txt << 'ROUTE'
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            // Get dokter data for more accurate name
            $dokter = \App\Models\Dokter::where('user_id', $user->id)->first();
            $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
            
            $userData = [
                'name' => $displayName,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
            ];
            
            return view('mobile.dokter.app', compact('token', 'userData'));
        })->name('mobile-app')->middleware('throttle:1000,1');
ROUTE

# Upload and execute the update
sshpass -p "$PASS" ssh -p $PORT -o StrictHostKeyChecking=no $USER@$HOST << 'EOF'
cd domains/dokterkuklinik.com/public_html

echo "🔍 Backing up current routes file..."
cp routes/web.php routes/web.php.backup.$(date +%Y%m%d_%H%M%S)

echo "🔍 Current mobile-app route:"
grep -A 10 "Route::get('/mobile-app'" routes/web.php | head -20

echo -e "\n📝 Creating temporary fix file..."
cat > /tmp/route_fix.php << 'PHPFIX'
<?php
$file = file_get_contents('routes/web.php');

// Find the mobile-app route and replace it
$pattern = '/Route::get\(\'\/mobile-app\', function.*?\}\)->name\(\'mobile-app\'\)->middleware\([^;]+\);/s';

$replacement = '        Route::get(\'/mobile-app\', function () {
            $user = auth()->user();
            $token = $user->createToken(\'mobile-app-dokter-\' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? \'Selamat Pagi\' : ($hour < 17 ? \'Selamat Siang\' : \'Selamat Malam\');
            
            // Get dokter data for more accurate name
            $dokter = \App\Models\Dokter::where(\'user_id\', $user->id)->first();
            $displayName = $dokter ? $dokter->nama_lengkap : $user->name;
            
            $userData = [
                \'name\' => $displayName,
                \'email\' => $user->email,
                \'greeting\' => $greeting,
                \'initials\' => strtoupper(substr($displayName ?? \'DA\', 0, 2))
            ];
            
            return view(\'mobile.dokter.app\', compact(\'token\', \'userData\'));
        })->name(\'mobile-app\')->middleware(\'throttle:1000,1\');';

if (preg_match($pattern, $file)) {
    $file = preg_replace($pattern, $replacement, $file);
    file_put_contents('routes/web.php', $file);
    echo "✅ Route updated successfully!\n";
} else {
    echo "❌ Could not find mobile-app route pattern\n";
}
PHPFIX

php /tmp/route_fix.php

echo -e "\n🧹 Clearing route cache..."
php artisan route:clear
php artisan cache:clear

echo -e "\n✅ Verifying updated route:"
grep -A 5 "dokter data for more accurate name" routes/web.php

echo -e "\n✅ Routes update complete!"
EOF

rm /tmp/updated_route.txt

echo -e "\n🎯 Routes updated on production!"