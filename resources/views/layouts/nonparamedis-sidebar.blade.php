<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Klinik Dokterku">
    <meta name="theme-color" content="#3b82f6">
    <title>{{ $title ?? 'Dashboard Non-Paramedis' }} - Klinik Dokterku</title>
    
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
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
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
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Critical mobile-first styles */
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            touch-action: manipulation;
            -webkit-text-size-adjust: 100%;
        }
        
        /* Prevent zoom on iOS inputs */
        input, textarea, select {
            font-size: 16px !important;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
        
        /* Clean tap highlighting */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Improve touch targets - minimum 44px for accessibility */
        button, a, [role="button"] {
            min-height: 44px !important;
            min-width: 44px !important;
        }
        
        /* Smooth scrolling optimization */
        html {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Hide scrollbars on mobile while maintaining functionality */
        @media (max-width: 768px) {
            ::-webkit-scrollbar {
                width: 0px;
                background: transparent;
            }
        }
    </style>
    
    @stack('styles')
</head>

<body class="bg-gray-50 font-sans antialiased h-full overflow-x-hidden">
    <div class="min-h-screen animate-fade-in">
        {{ $slot }}
    </div>
    
    <script>
        // Mobile debug utility
        window.mobileDebug = {
            log: function(message) {
                console.log('[Non-Paramedis] ' + message);
            }
        };
        
        // Initialize layout
        document.addEventListener('DOMContentLoaded', function() {
            window.mobileDebug.log('Layout loaded successfully!');
        });
    </script>
    
    @stack('scripts')
</body>
</html>