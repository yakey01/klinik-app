<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        <div class="space-y-8">
            {{-- Header --}}
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $this->getHeading() }}
                </h1>
                
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $this->getSubheading() }}
                </p>
            </div>

            {{-- Login Form --}}
            <div class="space-y-6">
                {{ $this->form }}
                
                <x-filament::button
                    type="submit"
                    size="lg"
                    class="w-full"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        Masuk ke Dashboard
                    </span>
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </x-filament::button>
            </div>

            {{-- Quick Login Hint --}}
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Demo Credentials:</strong><br>
                            Email: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">manajer@dokterku.com</code><br>
                            Password: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">password</code>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Features Preview --}}
            <div class="mt-8 grid grid-cols-2 gap-4 text-center">
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="w-8 h-8 mx-auto mb-2 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-gray-900 dark:text-white">KPI Analytics</p>
                </div>
                
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="w-8 h-8 mx-auto mb-2 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-gray-900 dark:text-white">Strategic Planning</p>
                </div>
            </div>
        </div>
    </x-filament-panels::form>

    <style>
        .fi-simple-main {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .fi-simple-main-ctn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .dark .fi-simple-main-ctn {
            background: rgba(17, 24, 39, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
    </style>
</x-filament-panels::page.simple>