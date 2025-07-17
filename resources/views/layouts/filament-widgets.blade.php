<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/widget-animations.js'])
    @livewireStyles
    @filamentStyles
</head>
<body class="font-sans antialiased">
    @yield('content')
    
    @livewireScripts
    @filamentScripts
    
    <!-- Widget Animation Initialization -->
    <script>
        // Ensure animations are properly initialized after Filament loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize widget animations
            if (window.WidgetAnimationController) {
                new window.WidgetAnimationController();
            }
        });
        
        // Re-initialize animations after Livewire updates
        document.addEventListener('livewire:navigated', function() {
            if (window.WidgetAnimationController) {
                setTimeout(() => {
                    new window.WidgetAnimationController();
                }, 100);
            }
        });
    </script>
</body>
</html>