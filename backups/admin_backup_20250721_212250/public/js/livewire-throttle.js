/**
 * Enterprise Livewire Request Throttling System
 * Prevents excessive /livewire/update requests
 */

(function() {
    if (typeof window.Livewire === "undefined") return;
    
    let requestQueue = [];
    let isProcessing = false;
    let lastRequestTime = 0;
    const THROTTLE_DELAY = 2000; // 2 seconds minimum between requests
    
    // Override Livewire request method
    const originalRequest = window.Livewire.request;
    
    window.Livewire.request = function(component, method, params, callback) {
        const now = Date.now();
        
        // Throttle rapid requests
        if (now - lastRequestTime < THROTTLE_DELAY) {
            console.log("🛡️ THROTTLED: Livewire request blocked to prevent loops");
            return;
        }
        
        lastRequestTime = now;
        return originalRequest.call(this, component, method, params, callback);
    };
    
    console.log("🛡️ Enterprise Livewire throttling active");
})();
