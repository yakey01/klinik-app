@props(['user'])

<div class="bg-gradient-to-r from-primary-900 via-primary-500 to-accent-400 px-5 py-6 text-white text-center relative overflow-hidden">
    <!-- Background glow effect -->
    <div class="absolute -top-full -left-full w-[300%] h-[300%] bg-gradient-radial from-accent-400/30 to-transparent animate-header-glow"></div>
    
    <div class="relative z-10">
        <h1 class="text-3xl font-extrabold mb-2 text-shadow-lg tracking-tight">KLINIK DOKTERKU</h1>
        <p class="text-sm opacity-90 font-medium">Sistem Manajemen Klinik Modern</p>
        
        <div class="bg-white/15 backdrop-blur-sm border border-white/20 rounded-2xl px-4 py-4 mt-6 flex items-center gap-4">
            <div class="w-13 h-13 bg-gradient-to-r from-accent-400 to-accent-500 rounded-full flex items-center justify-center text-white font-extrabold text-xl shadow-lg shadow-accent-400/40">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="text-left">
                <h3 class="font-bold text-lg text-shadow">{{ $user->name }}</h3>
                <p class="text-sm opacity-85 font-medium">Non-Paramedis • Klinik Dokterku</p>
            </div>
        </div>
    </div>
</div>