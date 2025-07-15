<div class="grid grid-cols-2 gap-4 mb-8">
    <!-- Presensi Card -->
    <a href="{{ route('nonparamedis.presensi') }}" class="menu-card active bg-white rounded-3xl p-7 shadow-lg transition-all duration-300 cursor-pointer border border-gray-200 relative overflow-hidden group hover:shadow-xl hover:-translate-y-2 hover:scale-105 block">
        <!-- Shimmer effect -->
        <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-primary-500/10 to-transparent transition-all duration-500 group-hover:left-full"></div>
        
        <div class="menu-icon w-14 h-14 bg-gradient-to-r from-primary-500 to-accent-400 rounded-2xl flex items-center justify-center mb-5 text-3xl shadow-lg shadow-primary-500/30 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6">
            📋
        </div>
        <div class="menu-title text-lg font-bold mb-2 tracking-tight">Presensi</div>
        <div class="menu-desc text-sm opacity-70 line-height-6 font-medium">Absensi masuk dan keluar kerja</div>
    </a>
    
    <!-- Jaspel Card -->
    <div class="menu-card bg-white rounded-3xl p-7 shadow-lg transition-all duration-300 cursor-pointer border border-gray-200 relative overflow-hidden group hover:shadow-xl hover:-translate-y-2 hover:scale-105">
        <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-primary-500/10 to-transparent transition-all duration-500 group-hover:left-full"></div>
        
        <div class="menu-icon w-14 h-14 bg-gradient-to-r from-primary-500 to-accent-400 rounded-2xl flex items-center justify-center mb-5 text-3xl shadow-lg shadow-primary-500/30 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6">
            💰
        </div>
        <div class="menu-title text-lg font-bold mb-2 tracking-tight">Jaspel</div>
        <div class="menu-desc text-sm opacity-70 line-height-6 font-medium">Jasa pelayanan medis</div>
    </div>
    
    <!-- Jadwal Card -->
    <a href="{{ route('nonparamedis.jadwal') }}" class="menu-card bg-white rounded-3xl p-7 shadow-lg transition-all duration-300 cursor-pointer border border-gray-200 relative overflow-hidden group hover:shadow-xl hover:-translate-y-2 hover:scale-105 block">
        <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-primary-500/10 to-transparent transition-all duration-500 group-hover:left-full"></div>
        
        <div class="menu-icon w-14 h-14 bg-gradient-to-r from-primary-500 to-accent-400 rounded-2xl flex items-center justify-center mb-5 text-3xl shadow-lg shadow-primary-500/30 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6">
            📅
        </div>
        <div class="menu-title text-lg font-bold mb-2 tracking-tight">Jadwal</div>
        <div class="menu-desc text-sm opacity-70 line-height-6 font-medium">Jadwal praktek medis</div>
    </a>
    
    <!-- Pasien Card -->
    <div class="menu-card bg-white rounded-3xl p-7 shadow-lg transition-all duration-300 cursor-pointer border border-gray-200 relative overflow-hidden group hover:shadow-xl hover:-translate-y-2 hover:scale-105">
        <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-primary-500/10 to-transparent transition-all duration-500 group-hover:left-full"></div>
        
        <div class="menu-icon w-14 h-14 bg-gradient-to-r from-primary-500 to-accent-400 rounded-2xl flex items-center justify-center mb-5 text-3xl shadow-lg shadow-primary-500/30 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6">
            👥
        </div>
        <div class="menu-title text-lg font-bold mb-2 tracking-tight">Pasien</div>
        <div class="menu-desc text-sm opacity-70 line-height-6 font-medium">Data pasien klinik</div>
    </div>
</div>

<style>
    .menu-card.active {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: white;
        transform: translateY(-4px);
        box-shadow: 0 15px 50px rgba(59, 130, 246, 0.3);
    }
    
    .menu-card.active .menu-icon {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
    }
</style>