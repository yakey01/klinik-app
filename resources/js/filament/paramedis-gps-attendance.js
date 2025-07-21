// Auto-location detection for Paramedis Attendance Forms
document.addEventListener('DOMContentLoaded', function() {
    console.log('GPS Attendance module loaded');

    // Wait for Filament to load completely
    setTimeout(function() {
        initializeGpsFeatures();
    }, 1000);
});

function initializeGpsFeatures() {
    // Add GPS detection buttons to map fields
    addGpsDetectionButton();
    
    // Auto-detect location when form loads (if permission granted)
    if (navigator.geolocation) {
        detectCurrentLocation();
    }
}

function addGpsDetectionButton() {
    const mapContainers = document.querySelectorAll('[data-field-wrapper="location"], [data-field-wrapper="checkout_location"]');
    
    mapContainers.forEach(container => {
        const fieldName = container.getAttribute('data-field-wrapper');
        
        // Skip if button already exists
        if (container.querySelector('.gps-detect-btn')) {
            return;
        }
        
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'gps-detect-btn inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-2';
        button.innerHTML = `
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Deteksi Lokasi Otomatis
        `;
        
        button.addEventListener('click', function() {
            detectLocationForField(fieldName);
        });
        
        // Insert button before the map
        const mapField = container.querySelector('.filament-google-maps-field');
        if (mapField) {
            mapField.parentNode.insertBefore(button, mapField);
        }
    });
}

function detectCurrentLocation() {
    if (!navigator.geolocation) {
        console.log('Geolocation not supported');
        return;
    }

    // Only auto-detect on create page and if no coordinates are set
    const isCreatePage = window.location.pathname.includes('/create');
    const hasExistingCoordinates = document.querySelector('input[name="latitude"]')?.value;
    
    if (!isCreatePage || hasExistingCoordinates) {
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            console.log('Auto-detected location:', lat, lng, 'Accuracy:', accuracy);
            
            // Set coordinates in form
            setLocationInForm('location', lat, lng, accuracy);
            
            // Show success notification
            showLocationNotification('success', 'Lokasi berhasil dideteksi otomatis');
        },
        function(error) {
            console.log('Auto-detection failed:', error.message);
            // Don't show error notification for auto-detection failure
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

function detectLocationForField(fieldName) {
    if (!navigator.geolocation) {
        showLocationNotification('error', 'GPS tidak didukung oleh browser Anda');
        return;
    }

    const button = document.querySelector(`[data-field-wrapper="${fieldName}"] .gps-detect-btn`);
    if (button) {
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Mendeteksi...
        `;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            console.log(`Location detected for ${fieldName}:`, lat, lng, 'Accuracy:', accuracy);
            
            // Set coordinates in form
            setLocationInForm(fieldName, lat, lng, accuracy);
            
            // Show success notification
            showLocationNotification('success', `Lokasi berhasil dideteksi! Akurasi: ${Math.round(accuracy)} meter`);
            
            // Reset button
            if (button) {
                button.disabled = false;
                button.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Deteksi Lokasi Otomatis
                `;
            }
        },
        function(error) {
            console.error(`Location detection failed for ${fieldName}:`, error);
            
            let errorMessage;
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Akses lokasi ditolak. Mohon izinkan akses lokasi pada browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Permintaan lokasi timeout. Silakan coba lagi.';
                    break;
                default:
                    errorMessage = 'Terjadi kesalahan saat mendeteksi lokasi.';
                    break;
            }
            
            showLocationNotification('error', errorMessage);
            
            // Reset button
            if (button) {
                button.disabled = false;
                button.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Deteksi Lokasi Otomatis
                `;
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 30000
        }
    );
}

function setLocationInForm(fieldName, lat, lng, accuracy) {
    // Determine field names based on field type
    let latField, lngField, accuracyField;
    
    if (fieldName === 'checkout_location') {
        latField = 'checkout_latitude';
        lngField = 'checkout_longitude';
        accuracyField = 'checkout_accuracy';
    } else {
        latField = 'latitude';
        lngField = 'longitude';
        accuracyField = 'accuracy';
    }
    
    // Set latitude
    const latInput = document.querySelector(`input[name="${latField}"]`);
    if (latInput) {
        latInput.value = lat.toFixed(8);
        latInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    // Set longitude
    const lngInput = document.querySelector(`input[name="${lngField}"]`);
    if (lngInput) {
        lngInput.value = lng.toFixed(8);
        lngInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    // Set accuracy
    const accuracyInput = document.querySelector(`input[name="${accuracyField}"]`);
    if (accuracyInput && accuracy) {
        accuracyInput.value = Math.round(accuracy);
        accuracyInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
    
    // Update map if Google Maps is available
    updateMapLocation(fieldName, lat, lng);
    
    // Reverse geocoding to get address
    reverseGeocode(lat, lng, fieldName);
}

function updateMapLocation(fieldName, lat, lng) {
    // Try to update the Google Maps instance
    setTimeout(() => {
        if (window.google && window.google.maps) {
            const mapContainer = document.querySelector(`[data-field-wrapper="${fieldName}"] .google-map`);
            if (mapContainer && mapContainer.googleMap) {
                const position = new google.maps.LatLng(lat, lng);
                mapContainer.googleMap.setCenter(position);
                
                // Update marker if exists
                if (mapContainer.marker) {
                    mapContainer.marker.setPosition(position);
                } else {
                    // Create new marker
                    mapContainer.marker = new google.maps.Marker({
                        position: position,
                        map: mapContainer.googleMap,
                        title: 'Lokasi Terpilih'
                    });
                }
            }
        }
    }, 500);
}

function reverseGeocode(lat, lng, fieldName) {
    // Use a simple reverse geocoding service
    const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.displayName) {
                // Set location name based on field type
                let locationNameField;
                if (fieldName === 'checkout_location') {
                    locationNameField = 'location_name_out';
                } else {
                    locationNameField = 'location_name_in';
                }
                
                const locationInput = document.querySelector(`input[name="${locationNameField}"]`);
                if (locationInput) {
                    locationInput.value = data.displayName;
                    locationInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        })
        .catch(error => {
            console.log('Reverse geocoding failed:', error);
        });
}

function showLocationNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Export functions for external use
window.ParamedisGpsAttendance = {
    detectLocationForField,
    setLocationInForm,
    showLocationNotification
};