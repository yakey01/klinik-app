<?php

echo "🧪 Testing Laravel 419 CSRF Error Resolution...\n\n";

// Test 1: Get CSRF token from the login page
echo "1. Testing CSRF token retrieval:\n";
$loginPageUrl = 'http://127.0.0.1:8000/login';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginPageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: $httpCode\n";

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

if ($csrfToken) {
    echo "   ✅ CSRF Token found: " . substr($csrfToken, 0, 20) . "...\n";
} else {
    echo "   ❌ CSRF Token not found\n";
}

// Test 2: Check excluded routes in CSRF middleware
echo "\n2. Testing CSRF exemptions:\n";
$exemptRoutes = [
    'http://127.0.0.1:8000/login',
    'http://127.0.0.1:8000/unified-login',
    'http://127.0.0.1:8000/api/test',
    'http://127.0.0.1:8000/paramedis/login'
];

foreach ($exemptRoutes as $route) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $route);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $routePath = parse_url($route, PHP_URL_PATH);
    $status = $httpCode == 419 ? "❌ CSRF Error" : "✅ No CSRF Error ($httpCode)";
    echo "   $routePath: $status\n";
}

// Test 3: Session configuration check
echo "\n3. Session configuration:\n";
if (file_exists('/tmp/cookies.txt')) {
    $cookies = file_get_contents('/tmp/cookies.txt');
    if (strpos($cookies, 'laravel_session') !== false) {
        echo "   ✅ Laravel session cookie found\n";
    } else {
        echo "   ❌ Laravel session cookie not found\n";
    }
} else {
    echo "   ❌ Cookie file not created\n";
}

// Clean up
if (file_exists('/tmp/cookies.txt')) {
    unlink('/tmp/cookies.txt');
}

echo "\n🎉 Test completed!\n";
echo "\n💡 Common 419 fixes:\n";
echo "   1. Clear caches: php artisan cache:clear\n";
echo "   2. Check CSRF exemptions in VerifyCsrfToken middleware\n";
echo "   3. Ensure CORS configuration allows credentials\n";
echo "   4. Check session configuration and storage\n";
echo "   5. Verify APP_KEY is set in .env\n";