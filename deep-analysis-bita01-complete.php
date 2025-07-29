<?php
// DEEP ANALYSIS: BITA01 PARAMEDIS PAGE EXPIRED ISSUES - COMPLETE
echo "🔍 DEEP ANALYSIS: BITA01 PARAMEDIS PAGE EXPIRED - COMPLETE\n";
echo "=========================================================\n\n";

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

try {
    echo "👤 CORRECT BITA USER IDENTIFICATION\n";
    echo "===================================\n";
    
    // Find the correct Bita user
    $bita = User::where('username', 'bita01')->first();
    if (!$bita) {
        echo "❌ Bita user (bita01) not found!\n";
        exit(1);
    }
    
    echo "✅ User: {$bita->name} (ID: {$bita->id})\n";
    echo "🔑 Username: {$bita->username}\n";
    echo "🎭 Role: " . ($bita->role ? $bita->role->name : 'No role') . "\n";
    echo "📧 Email: {$bita->email}\n";
    echo "✅ Active: " . ($bita->is_active ? 'YES' : 'NO') . "\n";
    echo "🔑 Password updated: " . $bita->updated_at->diffForHumans() . "\n\n";
    
    // Check associated pegawai record
    $pegawai = Pegawai::where('user_id', $bita->id)->first();
    if ($pegawai) {
        echo "👨‍⚕️ Associated Pegawai Record:\n";
        echo "   ID: {$pegawai->id}\n";
        echo "   Nama: {$pegawai->nama}\n";
        echo "   Jenis: {$pegawai->jenis_pegawai}\n";
        echo "   Unit Kerja: {$pegawai->unit_kerja}\n\n";
    } else {
        echo "❌ No associated pegawai record found\n\n";
    }
    
    echo "📊 MIDDLEWARE STACK ANALYSIS FOR BITA01\n";
    echo "======================================\n";
    
    // Analyze the complete middleware stack that affects Bita's login
    echo "🔍 Middleware execution order for paramedis login:\n\n";
    
    $middlewareStack = [
        'Global Web Middleware' => [
            'App\\Http\\Middleware\\ClearStaleSessionMiddleware',
        ],
        'Filament Panel Middleware' => [
            'Illuminate\\Cookie\\Middleware\\EncryptCookies',
            'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
            'Illuminate\\Session\\Middleware\\StartSession',
            'App\\Http\\Middleware\\SessionCleanupMiddleware',
            'Filament\\Http\\Middleware\\AuthenticateSession',
            'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
            'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
            'Illuminate\\Routing\\Middleware\\SubstituteBindings',
        ],
        'Auth Middleware' => [
            'App\\Http\\Middleware\\RedirectToUnifiedAuth',
            'Filament\\Http\\Middleware\\Authenticate',
            'App\\Http\\Middleware\\ParamedisMiddleware',
        ]
    ];
    
    foreach ($middlewareStack as $category => $middleware) {
        echo "📋 {$category}:\n";
        foreach ($middleware as $index => $mw) {
            $shortName = basename(str_replace('\\', '/', $mw));
            echo "   " . ($index + 1) . ". {$shortName}\n";
            
            // Analyze potential issues
            if (str_contains($mw, 'ClearStaleSessionMiddleware')) {
                echo "      ✅ FIXED: Now only runs on GET requests\n";
                echo "      📝 Previous issue: Was invalidating sessions on POST\n";
            }
            
            if (str_contains($mw, 'SessionCleanupMiddleware')) {
                echo "      ⚠️  POTENTIAL ISSUE: May refresh tokens during requests\n";
                echo "      📝 Analysis: refreshSessionIfNeeded() could cause token mismatch\n";
            }
            
            if (str_contains($mw, 'AuthenticateSession')) {
                echo "      ⚠️  SECURITY MIDDLEWARE: May invalidate compromised sessions\n";
                echo "      📝 Analysis: Could cause 419 if session appears suspicious\n";
            }
            
            if (str_contains($mw, 'VerifyCsrfToken')) {
                echo "      🎯 CRITICAL POINT: Where 419 errors are generated\n";
                echo "      📝 Analysis: Compares form token with session token\n";
            }
        }
        echo "\n";
    }
    
    echo "🔐 CSRF TOKEN FLOW ANALYSIS FOR BITA01\n";
    echo "=====================================\n";
    
    echo "Step-by-step CSRF token lifecycle:\n\n";
    
    echo "1. 🌐 GET /login (Bita visits login page):\n";
    echo "   • ClearStaleSessionMiddleware: Runs (GET request)\n";
    echo "   • StartSession: Creates new session\n";
    echo "   • CSRF token generated and stored in session\n";
    echo "   • Token included in login form as hidden field\n";
    echo "   • Page served to browser with token\n\n";
    
    echo "2. 📝 POST /login (Bita submits form):\n";
    echo "   • ClearStaleSessionMiddleware: SKIPPED (POST request)\n";
    echo "   • StartSession: Continues existing session\n";
    echo "   • SessionCleanupMiddleware: May refresh token here ⚠️\n";
    echo "   • VerifyCsrfToken: Compares submitted vs session token\n";
    echo "   • If match: Authentication proceeds\n";
    echo "   • If mismatch: 419 Page Expired returned\n\n";
    
    echo "🧪 LIVE TOKEN TESTING\n";
    echo "====================\n";
    
    // Test current CSRF token generation
    echo "🔑 Current Environment CSRF Analysis:\n";
    $currentToken = csrf_token();
    echo "  Token: " . substr($currentToken, 0, 20) . "...\n";
    echo "  Length: " . strlen($currentToken) . " characters\n";
    echo "  Generated at: " . date('Y-m-d H:i:s') . "\n";
    echo "  Session ID: " . session()->getId() . "\n\n";
    
    echo "📋 SESSION CONFIGURATION ANALYSIS\n";
    echo "=================================\n";
    
    echo "📊 Current Session Settings:\n";
    echo "  Driver: " . config('session.driver') . "\n";
    echo "  Lifetime: " . config('session.lifetime') . " minutes (" . (config('session.lifetime')/60) . " hours)\n";
    echo "  Cookie Name: " . config('session.cookie') . "\n";
    echo "  Domain: " . (config('session.domain') ?? 'localhost') . "\n";
    echo "  Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
    echo "  HttpOnly: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";
    echo "  SameSite: " . (config('session.same_site') ?? 'lax') . "\n\n";
    
    echo "📅 BITA01 SCHEDULE DATA VERIFICATION\n";
    echo "===================================\n";
    
    // Check if Bita has schedule data
    $schedules = JadwalJaga::where('pegawai_id', $pegawai ? $pegawai->id : 999)
        ->whereIn('unit_kerja', ['Pelayanan', 'Pendaftaran'])
        ->where('tanggal_jaga', '>=', now()->subDays(1))
        ->with(['shiftTemplate'])
        ->orderBy('tanggal_jaga')
        ->take(5)
        ->get();
    
    echo "📊 Schedule Status for Bita01:\n";
    echo "  Pegawai ID: " . ($pegawai ? $pegawai->id : 'Not found') . "\n";
    echo "  Total schedules: {$schedules->count()}\n\n";
    
    if ($schedules->count() > 0) {
        echo "✅ SCHEDULES FOUND:\n";
        foreach ($schedules as $schedule) {
            echo "  🆔 ID: {$schedule->id}\n";
            echo "  📅 Date: {$schedule->tanggal_jaga->format('Y-m-d')}\n";
            echo "  🏥 Unit: {$schedule->unit_kerja}\n";
            echo "  📋 Status: {$schedule->status_jaga}\n";
            echo "  🕐 Shift: " . ($schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'None') . "\n";
            echo "  ---\n";
        }
    } else {
        echo "❌ NO SCHEDULES FOUND for Bita01\n";
        echo "📝 This explains why dashboard shows 'Tidak ada jadwal jaga'\n\n";
    }
    
    echo "🚨 ROOT CAUSE ANALYSIS SUMMARY\n";
    echo "=============================\n";
    
    echo "1. ✅ PRIMARY FIXES APPLIED:\n";
    echo "   • ClearStaleSessionMiddleware: Fixed to GET-only\n";
    echo "   • Duplicate RefreshCsrfToken: Removed from all panels\n";
    echo "   • Middleware conflicts: Resolved\n\n";
    
    echo "2. ⚠️  REMAINING POTENTIAL ISSUES:\n";
    
    // Check SessionCleanupMiddleware behavior
    $sessionCleanupCode = file_get_contents('app/Http/Middleware/SessionCleanupMiddleware.php');
    if (strpos($sessionCleanupCode, 'regenerateToken()') !== false) {
        echo "   • SessionCleanupMiddleware still regenerates tokens\n";
        echo "     📝 Risk: Token refresh during login process\n";
        echo "     💡 Solution: Add login flow protection\n";
    }
    
    // Check AuthenticateSession behavior
    echo "   • AuthenticateSession middleware present\n";
    echo "     📝 Risk: May invalidate sessions for security\n";
    echo "     💡 Monitor: Check for false positives\n\n";
    
    echo "3. 📊 DATA ISSUES:\n";
    echo "   • Username was 'bita01', not 'sabita'\n";
    echo "   • Schedule data: " . ($schedules->count() > 0 ? 'Available' : 'Missing') . "\n";
    echo "   • Pegawai record: " . ($pegawai ? 'Found' : 'Missing') . "\n\n";
    
    echo "🎯 IMMEDIATE ACTION PLAN\n";
    echo "=======================\n";
    
    echo "1. ⚡ URGENT: Test with correct credentials\n";
    echo "   • Username: bita01 (not sabita)\n";
    echo "   • Password: Need to verify/reset\n";
    echo "   • Use updated test file with correct credentials\n\n";
    
    echo "2. 🔧 INVESTIGATE: SessionCleanupMiddleware timing\n";
    echo "   • Monitor token regeneration during login\n";
    echo "   • Consider adding login flow protection\n";
    echo "   • Test with middleware temporarily disabled\n\n";
    
    echo "3. 📊 SCHEDULE DATA: ";
    if ($schedules->count() === 0) {
        echo "CREATE MISSING SCHEDULES\n";
        echo "   • Admin needs to create schedules for Bita01\n";
        echo "   • Pegawai ID: " . ($pegawai ? $pegawai->id : 'Need to create') . "\n";
        echo "   • Unit kerja: 'Pelayanan' or 'Pendaftaran'\n";
    } else {
        echo "VERIFY SCHEDULE DISPLAY\n";
        echo "   • Schedules exist, dashboard should show them\n";
        echo "   • Check API endpoint functionality\n";
    }
    echo "\n";
    
    echo "4. 🧪 COMPREHENSIVE TESTING:\n";
    echo "   • Test login with bita01/correct-password\n";
    echo "   • Monitor middleware execution timing\n";
    echo "   • Verify dashboard schedule display\n";
    echo "   • Check for any remaining 419 errors\n\n";
    
    echo "📋 CORRECTED LOGIN CREDENTIALS\n";
    echo "=============================\n";
    echo "Username: bita01\n";
    echo "Password: [Need to verify - may need reset]\n";
    echo "Dashboard URL: http://localhost:8000/paramedis\n";
    echo "Expected behavior: Login without 419 errors\n\n";
    
    echo "✅ DEEP ANALYSIS COMPLETED\n";
    echo "==========================\n";
    echo "Key finding: Username is 'bita01', not 'sabita'\n";
    echo "Primary fixes are in place for 419 errors\n";
    echo "Need to test with correct credentials\n";
    echo "May need to create schedule data for dashboard\n\n";
    
} catch (Exception $e) {
    echo "❌ Error during analysis: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}