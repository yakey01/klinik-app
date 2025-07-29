{{-- Session Protection Script - Include in all authenticated layouts --}}
<meta name="session-lifetime" content="{{ config('session.lifetime') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@if(auth()->check())
<script>
    // Inline Session Protection for immediate execution
    (function() {
        'use strict';
        
        // Quick CSRF refresh on page load if needed
        const lastRefresh = sessionStorage.getItem('csrfLastRefresh');
        const now = Date.now();
        
        // Refresh if more than 25 minutes old
        if (!lastRefresh || (now - parseInt(lastRefresh)) > 25 * 60 * 1000) {
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
                document.querySelectorAll('meta[name="csrf-token"]').forEach(el => {
                    el.content = data.token;
                });
                document.querySelectorAll('input[name="_token"]').forEach(el => {
                    el.value = data.token;
                });
                sessionStorage.setItem('csrfLastRefresh', now.toString());
            })
            .catch(error => console.error('CSRF refresh failed:', error));
        }
        
        // Handle form submissions with token refresh
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.tagName === 'FORM' && form.method.toUpperCase() === 'POST') {
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput) {
                    const metaToken = document.querySelector('meta[name="csrf-token"]');
                    if (metaToken && tokenInput.value !== metaToken.content) {
                        tokenInput.value = metaToken.content;
                    }
                }
            }
        }, true);
        
        // Auto-save form data
        let formSaveTimer;
        document.addEventListener('input', function(e) {
            clearTimeout(formSaveTimer);
            formSaveTimer = setTimeout(() => {
                const forms = document.querySelectorAll('form');
                const formData = {};
                
                forms.forEach((form, index) => {
                    const data = new FormData(form);
                    const formObject = {};
                    
                    for (let [key, value] of data.entries()) {
                        if (!key.includes('password') && key !== '_token' && value) {
                            formObject[key] = value;
                        }
                    }
                    
                    if (Object.keys(formObject).length > 0) {
                        formData[form.id || index] = formObject;
                    }
                });
                
                if (Object.keys(formData).length > 0) {
                    sessionStorage.setItem('formAutoSave', JSON.stringify(formData));
                    sessionStorage.setItem('formAutoSaveTime', Date.now().toString());
                }
            }, 1000);
        });
        
        // Restore form data if available
        const savedForms = sessionStorage.getItem('formAutoSave');
        const saveTime = sessionStorage.getItem('formAutoSaveTime');
        
        if (savedForms && saveTime && (Date.now() - parseInt(saveTime)) < 30 * 60 * 1000) {
            try {
                const formData = JSON.parse(savedForms);
                setTimeout(() => {
                    Object.keys(formData).forEach(formId => {
                        const form = document.getElementById(formId) || document.querySelectorAll('form')[formId];
                        if (form) {
                            Object.keys(formData[formId]).forEach(fieldName => {
                                const field = form.elements[fieldName];
                                if (field && !field.value) {
                                    field.value = formData[formId][fieldName];
                                }
                            });
                        }
                    });
                }, 500);
            } catch (e) {
                console.error('Form restore failed:', e);
            }
        }
    })();
</script>

{{-- Load full session protection library --}}
@vite('resources/js/session-protection.js')
@endif