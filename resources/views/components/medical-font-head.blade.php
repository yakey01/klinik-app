{{--
    Medical Dashboard Font Loading Component
    Optimized self-hosted font loading for medical dashboard
    
    Usage: @include('components.medical-font-head')
--}}

{{-- Preload critical font files for immediate availability --}}
{{-- Inter Fonts --}}
<link rel="preload" href="{{ asset('fonts/inter/Inter-Regular.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="{{ asset('fonts/inter/Inter-Medium.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="{{ asset('fonts/inter/Inter-SemiBold.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">

{{-- Geist Fonts --}}
<link rel="preload" href="{{ asset('fonts/geist/Geist-Regular.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="{{ asset('fonts/geist/Geist-Medium.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="{{ asset('fonts/geist/Geist-SemiBold.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">

{{-- Self-hosted font CSS --}}
<link rel="stylesheet" href="{{ asset('css/fonts/medical-fonts.css') }}">
<link rel="stylesheet" href="{{ asset('fonts/geist/geist-self-hosted.css') }}">

{{-- Immediate font system CSS --}}
<style>
    /* Critical CSS for immediate font application */
    :root {
        --font-fallback: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --font-mono-fallback: "SF Mono", Monaco, Inconsolata, "Roboto Mono", Consolas, monospace;
    }
    
    /* Apply system fonts immediately */
    html, body {
        font-family: var(--font-fallback) !important;
    }
    
    .font-medical, .font-loading, .font-geist, .font-inter {
        font-family: var(--font-fallback) !important;
    }
    
    .font-mono, .font-geist-mono {
        font-family: var(--font-mono-fallback) !important;
    }
    
    /* Loading state indicator */
    .fonts-loading {
        opacity: 0.95;
    }
    
    /* Loaded state with smooth transition */
    .fonts-loaded {
        opacity: 1;
        transition: opacity 0.3s ease;
    }
    
    /* Enhanced medical typography */
    .medical-data, .dashboard-metric {
        font-variant-numeric: tabular-nums;
        font-feature-settings: 'tnum' 1;
    }
</style>

{{-- Font loading optimization script --}}
<script>
    // Ultra-lightweight font initialization 
    (function() {
        'use strict';
        
        // Mark start for performance monitoring
        const startTime = performance.now();
        
        // Add loading class immediately
        document.documentElement.classList.add('fonts-loading');
        
        // Set up success handler
        document.addEventListener('fontsLoaded', function(event) {
            document.documentElement.classList.remove('fonts-loading');
            document.documentElement.classList.add('fonts-loaded');
            
            const loadTime = performance.now() - startTime;
            console.log(`🎯 Font system activated in ${Math.round(loadTime)}ms via ${event.detail?.method || 'css'}`);
        });
        
        // Fallback timeout
        setTimeout(() => {
            if (!document.documentElement.classList.contains('fonts-loaded')) {
                document.documentElement.classList.remove('fonts-loading');
                document.documentElement.classList.add('fonts-loaded');
                console.log('🔄 Font system activated via timeout fallback');
            }
        }, 3000);
    })();
</script>

{{-- Load font optimization script asynchronously --}}
<script src="{{ asset('js/font-loader.js') }}" async></script>

{{-- Fallback for non-JS environments --}}
<noscript>
    <link rel="stylesheet" href="{{ asset('css/fonts/medical-fonts.css') }}">
    <style>
        html { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    </style>
</noscript>