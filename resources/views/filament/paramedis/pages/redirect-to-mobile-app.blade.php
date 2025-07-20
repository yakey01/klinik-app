<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 mx-auto text-primary-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Redirecting to Mobile App...</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">You will be redirected to the Paramedis Mobile App</p>
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500 mx-auto"></div>
        </div>
    </div>
    
    <script>
        // Redirect immediately
        window.location.href = '{{ route('paramedis.mobile-app') }}';
    </script>
</x-filament-panels::page>