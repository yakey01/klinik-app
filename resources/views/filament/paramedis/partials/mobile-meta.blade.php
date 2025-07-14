<!-- Mobile optimization meta tags -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Dokterku Paramedis">
<meta name="theme-color" content="#10b981">

<!-- PWA manifest for mobile app-like experience -->
<link rel="manifest" href="{{ asset('manifest.json') }}">

<!-- Touch icons -->
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icon-192x192.svg') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-192x192.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.ico') }}">

<!-- Prevent tap highlighting on mobile -->
<style>
    * {
        -webkit-tap-highlight-color: transparent;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    
    input, textarea, select {
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
    
    /* Smooth scrolling for mobile */
    html {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Optimize touch interactions */
    @media (max-width: 768px) {
        /* Hide scrollbars while maintaining functionality */
        ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        /* Ensure proper mobile layout */
        body {
            touch-action: manipulation;
            -webkit-text-size-adjust: 100%;
        }
    }
</style>