@props(['title' => 'Non-Paramedis Dashboard'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Klinik Dokterku">
    <meta name="theme-color" content="#3b82f6">
    <title>{{ $title }} - Klinik Dokterku</title>
    
    <!-- TailwindCSS v4 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        accent: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    animation: {
                        'background-shift': 'backgroundShift 10s ease-in-out infinite',
                        'glow-pulse': 'glowPulse 3s ease-in-out infinite',
                        'header-glow': 'headerGlow 8s ease-in-out infinite',
                        'notification-pulse': 'notificationPulse 2s ease-in-out infinite',
                        'rotate-glow': 'rotateGlow 3s linear infinite',
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        @keyframes backgroundShift {
            0%, 100% { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #fbbf24 100%); }
            50% { background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 50%, #f59e0b 100%); }
        }
        
        @keyframes glowPulse {
            0%, 100% { opacity: 0.1; }
            50% { opacity: 0.2; }
        }
        
        @keyframes headerGlow {
            0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
            50% { transform: translate(-50%, -50%) rotate(180deg); }
        }
        
        @keyframes notificationPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile optimizations */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            touch-action: manipulation;
            -webkit-text-size-adjust: 100%;
        }
        
        /* Touch targets */
        button, a, [role="button"] {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Hide scrollbars on mobile */
        @media (max-width: 768px) {
            ::-webkit-scrollbar {
                width: 0px;
                background: transparent;
            }
        }
    </style>
</head>
<body>
    {{ $slot }}
    
    <script>
        // Mobile debug utility
        window.mobileDebug = {
            log: function(message) {
                console.log('[Non-Paramedis] ' + message);
            }
        };
        
        // Initialize layout
        document.addEventListener('DOMContentLoaded', function() {
            window.mobileDebug.log('Page loaded: {{ $title }}');
        });
    </script>
</body>
</html>