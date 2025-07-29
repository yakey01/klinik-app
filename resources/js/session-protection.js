/**
 * Session Protection Library
 * Prevents page expired errors with intelligent session management
 */

class SessionProtection {
    constructor() {
        this.sessionLifetime = parseInt(document.querySelector('meta[name="session-lifetime"]')?.content || 1440) * 60 * 1000;
        this.warningTime = 30 * 60 * 1000; // 30 minutes before expiry
        this.refreshInterval = 30 * 60 * 1000; // Refresh token every 30 minutes
        this.lastActivity = Date.now();
        this.isActive = true;
        
        this.init();
    }
    
    init() {
        // Track user activity
        this.trackActivity();
        
        // Set up periodic token refresh
        this.setupTokenRefresh();
        
        // Set up session expiry warning
        this.setupExpiryWarning();
        
        // Save form data periodically
        this.setupFormDataSaving();
        
        // Handle browser back button
        this.handleBackButton();
        
        // Monitor AJAX requests
        this.monitorAjaxRequests();
    }
    
    trackActivity() {
        ['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
                this.isActive = true;
            }, true);
        });
        
        // Mark inactive after 5 minutes of no activity
        setInterval(() => {
            if (Date.now() - this.lastActivity > 5 * 60 * 1000) {
                this.isActive = false;
            }
        }, 60000);
    }
    
    setupTokenRefresh() {
        // Refresh CSRF token periodically when user is active
        setInterval(() => {
            if (this.isActive) {
                this.refreshCsrfToken();
            }
        }, this.refreshInterval);
    }
    
    refreshCsrfToken() {
        fetch('/csrf-token', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Update all CSRF tokens on the page
            document.querySelectorAll('meta[name="csrf-token"]').forEach(el => {
                el.content = data.token;
            });
            
            document.querySelectorAll('input[name="_token"]').forEach(el => {
                el.value = data.token;
            });
            
            // Update axios default headers if using axios
            if (window.axios) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
            }
            
            console.log('CSRF token refreshed successfully');
        })
        .catch(error => {
            console.error('Failed to refresh CSRF token:', error);
        });
    }
    
    setupExpiryWarning() {
        const warningTime = this.sessionLifetime - this.warningTime;
        
        if (warningTime > 0) {
            setTimeout(() => {
                this.showExpiryWarning();
            }, warningTime);
        }
    }
    
    showExpiryWarning() {
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"></div>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900">Sesi Akan Berakhir</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Sesi Anda akan berakhir dalam 30 menit. Klik "Perpanjang Sesi" untuk melanjutkan bekerja.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" onclick="sessionProtection.extendSession()" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                                Perpanjang Sesi
                            </button>
                            <button type="button" onclick="this.closest('.fixed').parentElement.remove()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    extendSession() {
        // Refresh CSRF token and reload page
        this.refreshCsrfToken();
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
    
    setupFormDataSaving() {
        // Auto-save form data every 30 seconds
        setInterval(() => {
            this.saveFormData();
        }, 30000);
        
        // Save on page unload
        window.addEventListener('beforeunload', () => {
            this.saveFormData();
        });
    }
    
    saveFormData() {
        const forms = document.querySelectorAll('form');
        const formData = {};
        
        forms.forEach((form, index) => {
            if (form.id || form.name) {
                const data = new FormData(form);
                const formObject = {};
                
                for (let [key, value] of data.entries()) {
                    // Don't save sensitive data
                    if (!key.includes('password') && key !== '_token') {
                        formObject[key] = value;
                    }
                }
                
                formData[form.id || form.name || index] = formObject;
            }
        });
        
        if (Object.keys(formData).length > 0) {
            sessionStorage.setItem('savedFormData', JSON.stringify(formData));
        }
    }
    
    restoreFormData() {
        const savedData = sessionStorage.getItem('savedFormData');
        if (savedData) {
            try {
                const formData = JSON.parse(savedData);
                
                Object.keys(formData).forEach(formId => {
                    const form = document.getElementById(formId) || document.querySelector(`form[name="${formId}"]`);
                    if (form) {
                        Object.keys(formData[formId]).forEach(fieldName => {
                            const field = form.elements[fieldName];
                            if (field) {
                                field.value = formData[formId][fieldName];
                            }
                        });
                    }
                });
                
                // Clear saved data after restore
                sessionStorage.removeItem('savedFormData');
            } catch (e) {
                console.error('Failed to restore form data:', e);
            }
        }
    }
    
    handleBackButton() {
        // Detect if we came from a 419 page
        if (document.referrer.includes('/419') || sessionStorage.getItem('csrf_refreshed')) {
            this.restoreFormData();
            sessionStorage.removeItem('csrf_refreshed');
        }
    }
    
    monitorAjaxRequests() {
        // Intercept fetch to handle 419 errors
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const response = await originalFetch(...args);
            
            if (response.status === 419) {
                // Try to refresh token and retry
                await this.refreshCsrfToken();
                
                // Update request with new token
                if (args[1] && args[1].headers) {
                    args[1].headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
                }
                
                // Retry request once
                return originalFetch(...args);
            }
            
            return response;
        };
        
        // Handle axios if present
        if (window.axios) {
            window.axios.interceptors.response.use(
                response => response,
                async error => {
                    if (error.response && error.response.status === 419) {
                        await this.refreshCsrfToken();
                        error.config.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
                        return window.axios.request(error.config);
                    }
                    return Promise.reject(error);
                }
            );
        }
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sessionProtection = new SessionProtection();
    });
} else {
    window.sessionProtection = new SessionProtection();
}