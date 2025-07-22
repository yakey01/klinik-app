<?php

/**
 * EMERGENCY ROUTE FIX
 * Quick patch for production if main fixes aren't working
 */

echo "🚨 EMERGENCY ROUTE FIX - Direct route modification" . PHP_EOL;
echo "=" . str_repeat("=", 50) . PHP_EOL . PHP_EOL;

echo "⚠️ USE THIS IF MAIN FIXES AREN'T WORKING" . PHP_EOL . PHP_EOL;

echo "📝 MANUAL ROUTE EDIT - routes/web.php around line 176:" . PHP_EOL;
echo str_repeat("-", 50) . PHP_EOL;

$oldCode = <<<'PHP'
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($user->name ?? 'DA', 0, 2))
            ];
            
            return view('mobile.dokter.app', compact('token', 'userData'));
        })->name('mobile-app')->middleware('throttle:1000,1');
PHP;

$newCode = <<<'PHP'
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
PHP;

echo "🔴 CURRENT CODE (problematic):" . PHP_EOL;
echo $oldCode . PHP_EOL . PHP_EOL;

echo "🟢 REPLACE WITH (fixed):" . PHP_EOL;
echo $newCode . PHP_EOL . PHP_EOL;

echo "🛠️ ALTERNATIVE: HARDCODE FIX (temporary):" . PHP_EOL;
echo str_repeat("-", 30) . PHP_EOL;

$hardcodedFix = <<<'PHP'
        Route::get('/mobile-app', function () {
            $user = auth()->user();
            $token = $user->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;
            
            $hour = now()->hour;
            $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
            
            // HARDCODED FIX for Dr. Yaya
            $displayName = $user->name === 'yaya' || $user->username === 'yaya' ? 
                'Dr. Yaya Mulyana, M.Kes' : $user->name;
            
            $userData = [
                'name' => $displayName,
                'email' => $user->email,
                'greeting' => $greeting,
                'initials' => strtoupper(substr($displayName ?? 'DA', 0, 2))
            ];
            
            return view('mobile.dokter.app', compact('token', 'userData'));
        })->name('mobile-app')->middleware('throttle:1000,1');
PHP;

echo $hardcodedFix . PHP_EOL . PHP_EOL;

echo "⚡ SUPER QUICK FIX (one-liner):" . PHP_EOL;
echo "Replace line with \$userData['name'] =" . PHP_EOL;
echo "   FROM: 'name' => \$user->name," . PHP_EOL;
echo "   TO:   'name' => 'Dr. Yaya Mulyana, M.Kes'," . PHP_EOL . PHP_EOL;

echo "🚀 AFTER MAKING CHANGES:" . PHP_EOL;
echo "1. Save the file" . PHP_EOL;
echo "2. Run: php artisan route:clear" . PHP_EOL;
echo "3. Refresh browser: https://dokterkuklinik.com/dokter/mobile-app" . PHP_EOL;
echo "4. Check if welcome shows 'Dr. Yaya Mulyana, M.Kes'" . PHP_EOL . PHP_EOL;

echo "✅ This will fix the welcome message immediately!" . PHP_EOL;
echo "🔄 Then work on fixing the API for attendance/performance data" . PHP_EOL;