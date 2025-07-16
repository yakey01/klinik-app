// Filament Component Support
window.FilamentComponents = window.FilamentComponents || {};

// Global component registry
window.registerFilamentComponent = function(name, component) {
    window.FilamentComponents[name] = component;
    console.log(`Registered Filament component: ${name}`);
};

// Component loader with retry mechanism
window.loadFilamentComponent = function(name, src, retries = 3) {
    return new Promise((resolve, reject) => {
        if (window.FilamentComponents[name]) {
            resolve(window.FilamentComponents[name]);
            return;
        }
        
        function attemptLoad(attempt) {
            const script = document.createElement('script');
            script.src = src;
            script.type = 'module';
            
            script.onload = () => {
                // Wait a bit for component to register
                setTimeout(() => {
                    if (window.FilamentComponents[name]) {
                        resolve(window.FilamentComponents[name]);
                    } else {
                        console.warn(`Component ${name} loaded but not registered`);
                        if (attempt < retries) {
                            attemptLoad(attempt + 1);
                        } else {
                            reject(new Error(`Failed to load component ${name} after ${retries} attempts`));
                        }
                    }
                }, 100);
            };
            
            script.onerror = () => {
                console.error(`Failed to load script: ${src}`);
                if (attempt < retries) {
                    setTimeout(() => attemptLoad(attempt + 1), 500);
                } else {
                    reject(new Error(`Failed to load script ${src} after ${retries} attempts`));
                }
            };
            
            document.head.appendChild(script);
        }
        
        attemptLoad(1);
    });
};

// Enhanced x-load directive
document.addEventListener('alpine:init', () => {
    Alpine.directive('load', (el, { value, modifiers, expression }, { evaluateLater, effect }) => {
        let evaluate = evaluateLater(expression);
        
        function loadScript(element) {
            let src = element.getAttribute('x-load-src');
            if (!src) return;
            
            // Remove version parameter if present
            src = src.split('?')[0];
            
            if (document.querySelector(`script[src="${src}"]`)) {
                // Script already loaded, just evaluate
                setTimeout(() => evaluate(), 50);
                return;
            }
            
            let script = document.createElement('script');
            script.src = src;
            script.type = 'module';
            
            script.onload = () => {
                console.log(`Loaded Filament script: ${src}`);
                // Give time for component to register
                setTimeout(() => {
                    try {
                        evaluate();
                    } catch (error) {
                        console.error('Error evaluating Alpine component:', error);
                        // Retry evaluation
                        setTimeout(() => evaluate(), 100);
                    }
                }, 100);
            };
            
            script.onerror = () => {
                console.error(`Failed to load Filament script: ${src}`);
                // Try to evaluate anyway in case component is available
                setTimeout(() => evaluate(), 100);
            };
            
            document.head.appendChild(script);
        }
        
        if (modifiers.includes('visible')) {
            let observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadScript(el);
                        observer.unobserve(el);
                    }
                });
            });
            observer.observe(el);
        } else {
            loadScript(el);
        }
    });
});

// Preload common Filament components
document.addEventListener('DOMContentLoaded', () => {
    const commonComponents = [
        '/js/filament/tables/components/table.js',
        '/js/filament/forms/components/select.js',
        '/js/filament/forms/components/date-time-picker.js'
    ];
    
    commonComponents.forEach(src => {
        if (!document.querySelector(`script[src="${src}"]`)) {
            const script = document.createElement('script');
            script.src = src;
            script.type = 'module';
            script.async = true;
            document.head.appendChild(script);
        }
    });
});