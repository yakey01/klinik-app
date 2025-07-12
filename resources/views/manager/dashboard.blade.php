<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Manajer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p>Anda masuk sebagai Manajer.</p>
                    
                    @if(isset($stats))
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-100 p-4 rounded">
                            <h4 class="font-semibold">Total Pasien</h4>
                            <p class="text-2xl">{{ number_format($stats['patients']) }}</p>
                        </div>
                        <div class="bg-green-100 p-4 rounded">
                            <h4 class="font-semibold">Total Pendapatan</h4>
                            <p class="text-2xl">Rp {{ number_format($stats['total_income']) }}</p>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded">
                            <h4 class="font-semibold">Menunggu Persetujuan</h4>
                            <p class="text-2xl">{{ number_format($stats['pending_approvals']) }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>