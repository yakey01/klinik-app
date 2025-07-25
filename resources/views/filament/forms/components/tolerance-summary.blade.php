<div class="tolerance-summary-modern p-6 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl border border-slate-200">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="summary-item bg-blue-50 p-3 rounded-lg border border-blue-200">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">⏰</span>
                <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Keterlambatan</span>
            </div>
            <div class="text-xl font-bold text-blue-800">{{ $late }}</div>
            <div class="text-xs text-blue-600">menit toleransi</div>
        </div>
        
        <div class="summary-item bg-green-50 p-3 rounded-lg border border-green-200">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">🚪</span>
                <span class="text-xs font-medium text-green-600 uppercase tracking-wide">Pulang Cepat</span>
            </div>
            <div class="text-xl font-bold text-green-800">{{ $early }}</div>
            <div class="text-xs text-green-600">menit toleransi</div>
        </div>
        
        <div class="summary-item bg-orange-50 p-3 rounded-lg border border-orange-200">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">☕</span>
                <span class="text-xs font-medium text-orange-600 uppercase tracking-wide">Istirahat</span>
            </div>
            <div class="text-xl font-bold text-orange-800">{{ $breakStr }}</div>
            <div class="text-xs text-orange-600">durasi istirahat</div>
        </div>
        
        <div class="summary-item bg-purple-50 p-3 rounded-lg border border-purple-200">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">⏱️</span>
                <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Batas Lembur</span>
            </div>
            <div class="text-xl font-bold text-purple-800">{{ $overtimeHours }}</div>
            <div class="text-xs text-purple-600">jam kerja normal</div>
        </div>
    </div>
</div>