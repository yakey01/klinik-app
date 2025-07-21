import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2 } from 'lucide-react';

interface GoogleMapsMobileProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  defaultLocation?: { lat: number; lng: number };
  height?: string;
  showGpsButton?: boolean;
  disabled?: boolean;
}

export function GoogleMapsMobile({ 
  onLocationSelect, 
  defaultLocation = { lat: -6.200000, lng: 106.816666 }, // Jakarta
  height = '300px',
  showGpsButton = true,
  disabled = false
}: GoogleMapsMobileProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const [map, setMap] = useState<google.maps.Map | null>(null);
  const [marker, setMarker] = useState<google.maps.Marker | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [locationAddress, setLocationAddress] = useState<string>('');

  // Initialize Google Maps
  useEffect(() => {
    const initMap = () => {
      // Enhanced safety checks
      if (!window.google || !mapRef.current) {
        console.log('Google Maps API or container not ready');
        return;
      }

      // Verify DOM element is properly attached
      if (!mapRef.current.parentNode || !document.contains(mapRef.current)) {
        console.log('Map container not properly attached to DOM');
        return;
      }

      const mapOptions: google.maps.MapOptions = {
        center: defaultLocation,
        zoom: 15,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        streetViewControl: true,
        mapTypeControl: true,
        fullscreenControl: false,
        zoomControl: true,
        gestureHandling: 'cooperative',
        styles: [
          {
            featureType: 'poi',
            elementType: 'labels',
            stylers: [{ visibility: 'on' }]
          }
        ]
      };

      const newMap = new google.maps.Map(mapRef.current, mapOptions);
      setMap(newMap);
      setIsLoading(false);

      // Add click listener to map
      newMap.addListener('click', async (e: google.maps.MapMouseEvent) => {
        if (disabled) return;
        
        const lat = e.latLng?.lat();
        const lng = e.latLng?.lng();
        
        if (lat && lng) {
          const roundedLat = parseFloat(lat.toFixed(8));
          const roundedLng = parseFloat(lng.toFixed(8));
          
          updateMarkerPosition(roundedLat, roundedLng);
          setCurrentLocation({ lat: roundedLat, lng: roundedLng });
          
          const address = await reverseGeocode(roundedLat, roundedLng);
          
          if (onLocationSelect) {
            onLocationSelect({
              lat: roundedLat,
              lng: roundedLng,
              address: address || ''
            });
          }
        }
      });

      // Create initial marker
      const initialMarker = new google.maps.Marker({
        position: defaultLocation,
        map: newMap,
        title: 'Lokasi Presensi',
        draggable: !disabled,
        animation: google.maps.Animation.DROP
      });

      setMarker(initialMarker);

      // Add marker drag listener
      initialMarker.addListener('dragend', async () => {
        if (disabled) return;
        
        const position = initialMarker.getPosition();
        if (position) {
          const lat = parseFloat(position.lat().toFixed(8));
          const lng = parseFloat(position.lng().toFixed(8));
          
          setCurrentLocation({ lat, lng });
          const address = await reverseGeocode(lat, lng);
          
          if (onLocationSelect) {
            onLocationSelect({
              lat,
              lng,
              address: address || ''
            });
          }
        }
      });

      // Auto-detect location on load
      if (showGpsButton && !disabled) {
        detectCurrentLocation(newMap, initialMarker);
      }
    };

    // Load Google Maps API if not already loaded
    if (!window.google) {
      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY || 'AIzaSyD_your_api_key_here'}&libraries=places`;
      script.async = true;
      script.defer = true;
      script.onload = () => {
        // Wait for React to finish rendering
        requestAnimationFrame(() => {
          setTimeout(initMap, 200);
        });
      };
      script.onerror = () => {
        console.error('Failed to load Google Maps API');
        setIsLoading(false);
      };
      document.head.appendChild(script);
    } else {
      // Ensure DOM is ready and React has finished rendering
      requestAnimationFrame(() => {
        setTimeout(initMap, 200);
      });
    }

    return () => {
      // Cleanup
      if (marker) {
        marker.setMap(null);
      }
      setMap(null);
      setMarker(null);
    };
  }, [defaultLocation, disabled]);

  const updateMarkerPosition = (lat: number, lng: number) => {
    if (marker && map) {
      const position = new google.maps.LatLng(lat, lng);
      marker.setPosition(position);
      map.setCenter(position);
      setCurrentLocation({ lat, lng });
      
      if (onLocationSelect) {
        onLocationSelect({
          lat,
          lng,
          address: locationAddress
        });
      }
    }
  };

  const detectCurrentLocation = (mapInstance?: google.maps.Map, markerInstance?: google.maps.Marker) => {
    if (!navigator.geolocation) {
      alert('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsGpsLoading(true);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = parseFloat(position.coords.latitude.toFixed(8));
        const lng = parseFloat(position.coords.longitude.toFixed(8));
        const accuracy = Math.round(position.coords.accuracy);

        console.log(`GPS Location detected: ${lat}, ${lng} (Accuracy: ${accuracy}m)`);

        updateMarkerPosition(lat, lng);
        
        // Set current location first
        setCurrentLocation({ lat, lng });

        // Then do reverse geocoding
        reverseGeocode(lat, lng).then((address) => {
          if (onLocationSelect) {
            onLocationSelect({
              lat,
              lng,
              accuracy,
              address: address || ''
            });
          }
        });

        setIsGpsLoading(false);

        // Show success notification
        showNotification('success', `Lokasi berhasil dideteksi! Akurasi: ${accuracy} meter`);
      },
      (error) => {
        console.error('GPS detection failed:', error);
        
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
        
        showNotification('error', errorMessage);
        setIsGpsLoading(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 30000
      }
    );
  };

  const reverseGeocode = async (lat: number, lng: number): Promise<string | null> => {
    try {
      // Use free reverse geocoding service
      const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`);
      const data = await response.json();
      
      if (data && data.displayName) {
        const address = data.displayName;
        setLocationAddress(address);
        return address;
      }
      return null;
    } catch (error) {
      console.log('Reverse geocoding failed:', error);
      return null;
    }
  };

  const showNotification = (type: 'success' | 'error', message: string) => {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
      type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
      <div class="flex items-center">
        <span>${message}</span>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 4 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 4000);
  };

  const handleGpsButtonClick = () => {
    if (map && marker) {
      detectCurrentLocation(map, marker);
    }
  };

  return (
    <div className="w-full space-y-3">
      {/* GPS Detection Button */}
      {showGpsButton && !disabled && (
        <Button 
          onClick={handleGpsButtonClick}
          disabled={isGpsLoading}
          className="w-full bg-blue-600 hover:bg-blue-700 text-white"
          size="sm"
        >
          {isGpsLoading ? (
            <>
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              Mendeteksi Lokasi...
            </>
          ) : (
            <>
              <Navigation className="w-4 h-4 mr-2" />
              Deteksi Lokasi GPS Otomatis
            </>
          )}
        </Button>
      )}

      {/* Map Container */}
      <div className="relative rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700">
        {isLoading && (
          <div 
            className="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
            style={{ height }}
          >
            <div className="text-center space-y-2">
              <Loader2 className="w-6 h-6 animate-spin mx-auto text-blue-600" />
              <p className="text-sm text-gray-600 dark:text-gray-300">Memuat peta...</p>
            </div>
          </div>
        )}
        
        <div 
          ref={mapRef} 
          style={{ height, width: '100%', minHeight: height }}
          className={`google-map-container ${isLoading ? 'opacity-0' : 'opacity-100 transition-opacity'}`}
        />
      </div>

      {/* Location Info */}
      {currentLocation && (
        <div className="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg space-y-2">
          <div className="flex items-center gap-2 text-sm">
            <MapPin className="w-4 h-4 text-blue-600" />
            <span className="font-medium text-gray-900 dark:text-white">Lokasi Terpilih:</span>
          </div>
          
          <div className="text-sm text-gray-600 dark:text-gray-300 space-y-1">
            <p><strong>Koordinat:</strong> {currentLocation.lat.toFixed(6)}, {currentLocation.lng.toFixed(6)}</p>
            {locationAddress && (
              <p><strong>Alamat:</strong> {locationAddress}</p>
            )}
          </div>
        </div>
      )}

      {/* Instructions */}
      {!disabled && (
        <div className="text-xs text-gray-500 dark:text-gray-400 text-center">
          Klik pada peta atau gunakan GPS untuk memilih lokasi presensi
        </div>
      )}
    </div>
  );
}

export default GoogleMapsMobile;