<div class="bg-gradient-to-r from-white to-gray-50 px-0 py-5 border-t border-gray-200 flex justify-around items-center shadow-lg shadow-black/5">
    <!-- Home -->
    <a href="{{ route('nonparamedis.dashboard') }}" class="nav-item active flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all duration-300 cursor-pointer">
        <div class="nav-icon text-2xl transition-all duration-300">🏠</div>
        <div class="nav-label text-xs font-semibold tracking-wide">Home</div>
    </a>
    
    <!-- Presensi -->
    <a href="{{ route('nonparamedis.presensi') }}" class="nav-item flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all duration-300 cursor-pointer text-gray-500 hover:bg-primary-500/10 hover:text-primary-500 hover:-translate-y-0.5">
        <div class="nav-icon text-2xl transition-all duration-300">📋</div>
        <div class="nav-label text-xs font-semibold tracking-wide">Presensi</div>
    </a>
    
    <!-- Jaspel -->
    <div class="nav-item flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all duration-300 cursor-pointer text-gray-500 hover:bg-primary-500/10 hover:text-primary-500 hover:-translate-y-0.5">
        <div class="nav-icon text-2xl transition-all duration-300">💰</div>
        <div class="nav-label text-xs font-semibold tracking-wide">Jaspel</div>
    </div>
    
    <!-- Jadwal -->
    <a href="{{ route('nonparamedis.jadwal') }}" class="nav-item flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all duration-300 cursor-pointer text-gray-500 hover:bg-primary-500/10 hover:text-primary-500 hover:-translate-y-0.5">
        <div class="nav-icon text-2xl transition-all duration-300">📅</div>
        <div class="nav-label text-xs font-semibold tracking-wide">Jadwal</div>
    </a>
    
    <!-- Profil -->
    <div class="nav-item flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all duration-300 cursor-pointer text-gray-500 hover:bg-primary-500/10 hover:text-primary-500 hover:-translate-y-0.5">
        <div class="nav-icon text-2xl transition-all duration-300">👤</div>
        <div class="nav-label text-xs font-semibold tracking-wide">Profil</div>
    </div>
</div>

<style>
    .nav-item.active {
        background: linear-gradient(135deg, #3b82f6, #fbbf24);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }
    
    .nav-item.active .nav-icon {
        transform: scale(1.1);
    }
</style>