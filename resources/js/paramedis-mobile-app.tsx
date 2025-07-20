import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/paramedis/App';
import './components/paramedis/styles.css';

// Simple and safe app initialization
function initializeApp() {
    console.log('🚀 Paramedis Mobile App: Starting initialization...');
    
    try {
        const container = document.getElementById('paramedis-app');
        
        if (!container) {
            console.error('Container element #paramedis-app not found!');
            return;
        }
        
        // Hide loading spinner
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        // Create React root
        const root = createRoot(container);
        root.render(<App />);
        console.log('✅ React app rendered successfully!');
        
    } catch (e) {
        console.error('❌ App initialization failed:', e);
        // Show fallback content
        const container = document.getElementById('paramedis-app');
        if (container) {
            container.innerHTML = '<div style="padding: 20px; text-align: center; background: #f0f0f0; color: #333; border-radius: 8px; margin: 20px;">⚠️ App loading failed. Please refresh and try again.</div>';
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp, { once: true });
} else {
    initializeApp();
}