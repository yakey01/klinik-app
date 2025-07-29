<?php

// Simple standalone script to test login without Laravel middleware interference

echo "🧪 Simple Login Test (Bypassing Middleware)\n\n";

// Create a cookie jar to maintain session
$cookieJar = tempnam(sys_get_temp_dir(), 'cookies');

// Step 1: Get the login page and extract CSRF token
echo "1. Getting login page and CSRF token...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Test Browser)');

$loginPage = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Login page HTTP code: $httpCode\n";

// Extract CSRF token from the page
preg_match('/<input[^>]*name=["\']_token["\'][^>]*value=["\']([^"\']+)["\']/', $loginPage, $matches);
$csrfToken = $matches[1] ?? null;

if ($csrfToken) {
    echo "   ✅ CSRF token extracted: " . substr($csrfToken, 0, 20) . "...\n";
} else {
    echo "   ❌ Could not extract CSRF token\n";
    echo "   Searching for token pattern in page...\n";
    
    // Try alternative token patterns
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage, $metaMatches);
    if (!empty($metaMatches[1])) {
        $csrfToken = $metaMatches[1];
        echo "   ✅ CSRF token found in meta tag: " . substr($csrfToken, 0, 20) . "...\n";
    }
}

// Step 2: Attempt login with token
if ($csrfToken) {
    echo "\n2. Attempting login with CSRF token...\n";
    
    $postData = http_build_query([
        '_token' => $csrfToken,
        'email_or_username' => 'naning@dokterku.com',
        'password' => 'password123',
        'remember' => '0'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects to see the response
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Test Browser)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Login attempt HTTP code: $httpCode\n";
    
    if ($httpCode == 419) {
        echo "   ❌ Still getting 419 CSRF error\n";
        echo "   Response snippet: " . substr(strip_tags($response), 0, 100) . "...\n";
    } elseif ($httpCode == 302) {
        echo "   ✅ Got redirect (likely successful or validation error)\n";
    } elseif ($httpCode == 422) {
        echo "   ⚠️ Validation error (likely wrong credentials)\n";
    } else {
        echo "   ℹ️ Unexpected response code\n";
        echo "   Response snippet: " . substr(strip_tags($response), 0, 200) . "...\n";
    }
} else {
    echo "\n❌ Cannot proceed without CSRF token\n";
}

// Step 3: Try the API login route as alternative
echo "\n3. Testing API login route as alternative...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/paramedis/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email_or_username' => 'naning@dokterku.com',
    'password' => 'password123'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   API login HTTP code: $httpCode\n";
if ($httpCode != 419) {
    echo "   ✅ API route doesn't have CSRF issues\n"; 
} else {
    echo "   ❌ API route also has CSRF issues\n";
}

// Cleanup
unlink($cookieJar);

echo "\n🎯 Summary:\n";
echo "   - Test completed with detailed diagnostics\n";
echo "   - Check the HTTP codes above to identify the issue\n";
echo "   - If 419 persists, the CSRF exemption isn't working\n";
echo "   - If 302/422, CSRF is working but credentials might be wrong\n";