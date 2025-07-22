<?php
// Force browser refresh with aggressive headers

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');

// Set timestamp for cache busting
$timestamp = time();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Refresh - DOKTERKU</title>
    <script>
        // Aggressive cache clearing
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
        
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) {
                    caches.delete(name);
                }
            });
        }
        
        // Clear localStorage
        localStorage.clear();
        sessionStorage.clear();
        
        // Force reload with timestamp
        setTimeout(function() {
            window.location.href = '/dokter/mobile-app?v=' + <?= $timestamp ?> + '&force=1';
        }, 500);
    </script>
</head>
<body>
    <h1>Clearing cache and refreshing...</h1>
    <p>Cache version: <?= $timestamp ?></p>
    <p>Please wait...</p>
</body>
</html>