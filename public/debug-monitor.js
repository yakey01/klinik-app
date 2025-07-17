// DEBUG MONITOR: Ultimate debugging for document.body.classList access
// This script will monitor and log ANY attempts to access document.body.classList

(function() {
    'use strict';
    
    console.log('🔍 DEBUG MONITOR: Starting ultimate document.body.classList monitoring...');
    
    // Track all access attempts
    let accessAttempts = [];
    let monitoringActive = true;
    
    // Create comprehensive monitoring
    function setupUltimateMonitoring() {
        if (!document.body) {
            console.log('🔍 DEBUG: document.body not available yet, retrying...');
            setTimeout(setupUltimateMonitoring, 100);
            return;
        }
        
        const originalClassList = document.body.classList;
        
        // Create monitored classList proxy
        const monitoredClassList = new Proxy(originalClassList, {
            get: function(target, property, receiver) {
                if (monitoringActive) {
                    const stack = new Error().stack;
                    const timestamp = new Date().toISOString();
                    
                    accessAttempts.push({
                        timestamp,
                        property,
                        stack: stack,
                        source: 'document.body.classList.' + property
                    });
                    
                    console.warn('🚨 DEBUG MONITOR: document.body.classList access detected!', {
                        property,
                        timestamp,
                        stack: stack.split('\n').slice(0, 5).join('\n')
                    });
                    
                    // Show visual alert for critical methods
                    if (['add', 'remove', 'toggle'].includes(property)) {
                        console.error('🔥 CRITICAL: document.body.classList.' + property + ' accessed!');
                        console.error('🔥 STACK TRACE:', stack);
                    }
                }
                
                return Reflect.get(target, property, receiver);
            },
            
            set: function(target, property, value, receiver) {
                if (monitoringActive) {
                    console.warn('🚨 DEBUG MONITOR: document.body.classList SET operation:', property, value);
                }
                return Reflect.set(target, property, value, receiver);
            }
        });
        
        // Replace classList with monitored version
        Object.defineProperty(document.body, 'classList', {
            get: function() {
                return monitoredClassList;
            },
            set: function(value) {
                console.warn('🚨 DEBUG MONITOR: document.body.classList SET to:', value);
            },
            configurable: true,
            enumerable: true
        });
        
        console.log('🔍 DEBUG MONITOR: Ultimate monitoring activated for document.body.classList');
    }
    
    // Global error handler to catch any related errors
    window.addEventListener('error', function(event) {
        if (event.message && event.message.includes('document.body.classList')) {
            console.error('🔥 DEBUG MONITOR: Caught document.body.classList error!', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                stack: event.error ? event.error.stack : 'No stack available'
            });
            
            // Log all recent access attempts
            console.table(accessAttempts.slice(-10));
        }
    });
    
    // Report function for debugging
    window.getClassListAccessReport = function() {
        console.log('📊 DOCUMENT.BODY.CLASSLIST ACCESS REPORT:');
        console.table(accessAttempts);
        return accessAttempts;
    };
    
    // Stop monitoring function
    window.stopClassListMonitoring = function() {
        monitoringActive = false;
        console.log('🔍 DEBUG MONITOR: Monitoring stopped');
    };
    
    // Start monitoring function
    window.startClassListMonitoring = function() {
        monitoringActive = true;
        console.log('🔍 DEBUG MONITOR: Monitoring started');
    };
    
    // Setup monitoring when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupUltimateMonitoring);
    } else {
        setupUltimateMonitoring();
    }
    
    // Periodic report every 10 seconds if there are access attempts
    setInterval(function() {
        if (accessAttempts.length > 0) {
            console.log('📊 DEBUG MONITOR: ' + accessAttempts.length + ' document.body.classList access attempts detected');
            console.log('Recent attempts:', accessAttempts.slice(-3));
        }
    }, 10000);
    
})();