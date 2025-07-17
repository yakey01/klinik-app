import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/dokter/App';
import './components/dokter/styles.css';

// ULTRA-DEFENSIVE app initialization with comprehensive error detection
function initializeApp() {
    console.log('🚀 ULTRAFIX: Starting dokter mobile app initialization...');
    
    try {
        // Debug: Check what's available
        console.log('ULTRAFIX: document defined?', typeof document !== 'undefined');
        console.log('ULTRAFIX: document.getElementById available?', typeof document.getElementById === 'function');
        console.log('ULTRAFIX: document.body exists?', document.body !== null);
        console.log('ULTRAFIX: document.body.classList available?', document.body && document.body.classList);
        
        // Multiple safety checks
        if (typeof document === 'undefined' || !document.getElementById) {
            console.error('ULTRAFIX: Document not ready for initialization');
            return;
        }
        
        const container = document.getElementById('dokter-app');
        const loadingElement = document.getElementById('loading');
        
        console.log('ULTRAFIX: Container found?', !!container);
        console.log('ULTRAFIX: Loading element found?', !!loadingElement);
        
        if (container) {
            // Hide loading spinner safely
            try {
                if (loadingElement && loadingElement.style) {
                    loadingElement.style.display = 'none';
                    console.log('ULTRAFIX: Loading element hidden successfully');
                }
            } catch (e) {
                console.log('ULTRAFIX: Loading element error (safe to ignore):', e);
            }
            
            // Create React root with error boundary
            try {
                console.log('ULTRAFIX: Creating React root...');
                const root = createRoot(container);
                root.render(<App />);
                console.log('ULTRAFIX: React app rendered successfully! 🎉');
            } catch (e) {
                console.error('ULTRAFIX: React initialization failed:', e);
                // Show fallback content
                if (container) {
                    container.innerHTML = '<div style="padding: 20px; text-align: center; background: #f0f0f0; color: #333;">⚠️ App loading failed. Please refresh and try again.</div>';
                }
            }
        } else {
            console.error('ULTRAFIX: Container element #dokter-app not found!');
        }
    } catch (e) {
        console.error('ULTRAFIX: App initialization completely failed:', e);
        console.error('ULTRAFIX: Error stack:', e.stack);
    }
}

// ULTIMATE+ initialization strategy with Alpine.js protection
function ultimateInitialize() {
    console.log('🎯 ULTIMATE+: Starting completely isolated initialization with Alpine.js protection...');
    
    // ULTIMATE+ SAFETY: Only run on dokter mobile app page
    if (typeof window !== 'undefined' && 
        window.location && 
        !window.location.pathname.includes('/dokter/mobile-app')) {
        console.log('ULTIMATE+: Not dokter mobile app page, skipping initialization');
        return;
    }
    
    // ULTIMATE+ ALPINE.JS PROTECTION: Block Alpine.js from interfering
    if (typeof window !== 'undefined') {
        // Set isolation flag
        window.__DOKTERKU_ISOLATED__ = true;
        
        // Override any Alpine.js access to document.body
        if (window.Alpine && typeof window.Alpine === 'object') {
            console.log('🛡️ ULTIMATE+: Detected Alpine.js, implementing protection...');
            
            // Prevent Alpine from auto-starting on this page
            if (window.Alpine.start && typeof window.Alpine.start === 'function') {
                const originalStart = window.Alpine.start;
                window.Alpine.start = function() {
                    if (window.__DOKTERKU_ISOLATED__) {
                        console.log('🛡️ ULTIMATE+: Blocked Alpine.js start on isolated page');
                        return;
                    }
                    return originalStart.apply(this, arguments);
                };
            }
        }
        
        // Block any external scripts from accessing document.body.classList
        if (document.body) {
            const originalClassList = document.body.classList;
            let bodyClassListAccessed = false;
            
            Object.defineProperty(document.body, 'classList', {
                get: function() {
                    if (!bodyClassListAccessed) {
                        bodyClassListAccessed = true;
                        console.warn('🚨 ULTIMATE+: External script attempted to access document.body.classList - redirecting to documentElement');
                    }
                    // Return documentElement classList instead
                    return document.documentElement.classList;
                },
                configurable: true
            });
        }
    }
    
    // ULTIMATE+ ISOLATION: Prevent conflicts with other React apps
    if (typeof document === 'undefined') {
        console.error('ULTIMATE+: Document not available');
        return;
    }
    
    // ULTIMATE STRATEGY: Multiple fallback timings
    const tryInitialize = (attempt: number = 1) => {
        console.log(`ULTIMATE: Initialization attempt ${attempt}`);
        
        try {
            if (document.readyState === 'complete' || 
                (document.readyState === 'interactive' && document.body)) {
                initializeApp();
                return;
            }
            
            if (attempt < 5) {
                setTimeout(() => tryInitialize(attempt + 1), 100 * attempt);
            } else {
                console.error('ULTIMATE: All initialization attempts failed');
            }
        } catch (e) {
            console.error(`ULTIMATE: Attempt ${attempt} failed:`, e);
            if (attempt < 5) {
                setTimeout(() => tryInitialize(attempt + 1), 200 * attempt);
            }
        }
    };
    
    // ULTIMATE EXECUTION
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('ULTIMATE: DOM loaded, starting initialization');
            tryInitialize();
        }, { once: true });
    } else {
        tryInitialize();
    }
}

// ULTIMATE+ LAUNCH with complete error isolation and Alpine.js protection
try {
    console.log('🚀 ULTIMATE+: Launching dokter mobile app with complete isolation and Alpine.js protection...');
    ultimateInitialize();
} catch (e) {
    console.error('🔥 ULTIMATE+: Launch failed completely:', e);
    console.error('ULTIMATE+: Error stack:', e.stack);
    
    // ULTIMATE+ FALLBACK: Direct initialization after delay with protection
    setTimeout(() => {
        try {
            console.log('🆘 ULTIMATE+: Attempting emergency initialization with Alpine.js protection...');
            
            // Apply protection even in emergency mode
            if (typeof window !== 'undefined' && document.body) {
                window.__DOKTERKU_ISOLATED__ = true;
                
                Object.defineProperty(document.body, 'classList', {
                    get: function() {
                        console.warn('🚨 EMERGENCY: Redirecting document.body.classList to documentElement');
                        return document.documentElement.classList;
                    },
                    configurable: true
                });
            }
            
            initializeApp();
        } catch (emergencyError) {
            console.error('🆘 ULTIMATE+: Emergency initialization also failed:', emergencyError);
        }
    }, 2000);
}