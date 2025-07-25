<div class="work-location-tolerance-actions-wrapper">
    <div class="quick-actions-container flex items-center justify-between gap-4 p-6 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border border-slate-200">
        <div class="quick-actions-info">
            <h4 class="text-lg font-bold text-slate-800 mb-1">💾 Simpan Pengaturan</h4>
            <p class="text-sm text-slate-600">Pastikan semua pengaturan toleransi sudah sesuai sebelum menyimpan</p>
        </div>
        <div class="quick-actions-buttons flex gap-3">
            <button 
                type="button" 
                onclick="resetWorkLocationToleranceSettings()" 
                class="reset-btn px-6 py-3 bg-slate-500 hover:bg-slate-600 text-white font-semibold rounded-lg shadow-md transition-all duration-200 flex items-center gap-2"
            >
                <span>🔄</span>
                <span>Reset Default</span>
            </button>
            <button 
                type="submit" 
                class="save-btn px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg shadow-md transition-all duration-200 flex items-center gap-2"
            >
                <span>✅</span>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </div>
    
    <script>
        function resetWorkLocationToleranceSettings() {
            if (confirm("Apakah Anda yakin ingin mengatur ulang semua pengaturan toleransi ke nilai default?")) {
                // Reset all tolerance fields to default values
                const lateInput = document.querySelector("input[name='late_tolerance_minutes']");
                const earlyInput = document.querySelector("input[name='early_departure_tolerance_minutes']");
                const breakInput = document.querySelector("input[name='break_time_minutes']");
                const overtimeInput = document.querySelector("input[name='overtime_threshold_minutes']");
                
                if (lateInput) {
                    lateInput.value = 15;
                    lateInput.dispatchEvent(new Event("input", { bubbles: true }));
                }
                if (earlyInput) {
                    earlyInput.value = 15;
                    earlyInput.dispatchEvent(new Event("input", { bubbles: true }));
                }
                if (breakInput) {
                    breakInput.value = 60;
                    breakInput.dispatchEvent(new Event("input", { bubbles: true }));
                }
                if (overtimeInput) {
                    overtimeInput.value = 480;
                    overtimeInput.dispatchEvent(new Event("input", { bubbles: true }));
                }
                
                // Show success notification using Filament notification system
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        title: '✅ Reset Berhasil!',
                        body: 'Pengaturan toleransi telah direset ke nilai default',
                        type: 'success',
                        duration: 3000
                    }
                }));
            }
        }
    </script>
</div>