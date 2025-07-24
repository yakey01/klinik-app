<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Dokter') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p>Anda masuk sebagai Dokter.</p>
                </div>
            </div>

            <!-- Jaspel Summary Widget -->
            <div>
                @livewire('doctor.jaspel-summary-widget')
            </div>

            <!-- Jaspel History Table -->
            <div>
                @livewire('doctor.jaspel-history-table')
            </div>
        </div>
    </div>
</x-app-layout>