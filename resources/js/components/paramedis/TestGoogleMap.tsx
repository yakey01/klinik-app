import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2, CheckCircle, AlertCircle, ExternalLink } from 'lucide-react';

interface TestGoogleMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
}

export function TestGoogleMap({ onLocationSelect, height = '300px' }: TestGoogleMapProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [mapReady, setMapReady] = useState(false);
  const [googleMap, setGoogleMap] = useState<google.maps.Map | null>(null);
  const [marker, setMarker] = useState<google.maps.Marker | null>(null);
  const [hasApiKeyError, setHasApiKeyError] = useState(false);

  // Initialize Google Maps
  useEffect(() => {
    let mounted = true;

    const initializeMap = async () => {
      if (!mounted || !mapRef.current) {
        return;
      }

      try {
        console.log('🗺️ Initializing Google Map...');
        
        // Check if Google Maps is available
        if (!window.google || !window.google.maps) {
          throw new Error('Google Maps API not loaded');
        }

        // Create map with explicit options
        const mapOptions: google.maps.MapOptions = {
          center: { lat: -7.808758, lng: 111.962646 }, // User's coordinates
          zoom: 16,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          disableDefaultUI: false,
          zoomControl: true,
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: false,
          gestureHandling: 'greedy',
          backgroundColor: '#f0f0f0'
        };

        const map = new google.maps.Map(mapRef.current, mapOptions);

        // Create marker
        const newMarker = new google.maps.Marker({
          position: { lat: -7.808758, lng: 111.962646 },
          map: map,
          title: 'Lokasi Presensi',
          draggable: true,
          animation: google.maps.Animation.DROP
        });

        // Add click listener to map
        map.addListener('click', (event: google.maps.MapMouseEvent) => {
          if (!mounted || !event.latLng) return;
          
          const lat = parseFloat(event.latLng.lat().toFixed(8));
          const lng = parseFloat(event.latLng.lng().toFixed(8));
          
          newMarker.setPosition({ lat, lng });
          setCurrentLocation({ lat, lng });
          
          if (onLocationSelect) {
            onLocationSelect({ lat, lng });
          }
          
          console.log(`📍 Map clicked: ${lat}, ${lng}`);
        });

        // Add drag listener to marker
        newMarker.addListener('dragend', () => {
          if (!mounted) return;
          
          const position = newMarker.getPosition();
          if (position) {
            const lat = parseFloat(position.lat().toFixed(8));
            const lng = parseFloat(position.lng().toFixed(8));
            
            setCurrentLocation({ lat, lng });
            
            if (onLocationSelect) {
              onLocationSelect({ lat, lng });
            }
            
            console.log(`🎯 Marker dragged: ${lat}, ${lng}`);
          }
        });

        // Set initial location
        setCurrentLocation({ lat: -7.808758, lng: 111.962646 });
        if (onLocationSelect) {
          onLocationSelect({ lat: -7.808758, lng: 111.962646 });
        }

        // Wait for map to be idle (fully loaded)
        google.maps.event.addListenerOnce(map, 'idle', () => {
          if (mounted) {
            setGoogleMap(map);
            setMarker(newMarker);
            setMapReady(true);
            setIsLoading(false);
            setHasApiKeyError(false);
            console.log('✅ Google Map initialized successfully!');
          }
        });

        // Listen for API key related errors
        google.maps.event.addListener(map, 'idle', () => {
          const watermarkElements = document.querySelectorAll('[style*="For development purposes only"]');
          if (watermarkElements.length > 0) {
            setHasApiKeyError(true);
          }
        });

      } catch (error) {
        console.error('❌ Map initialization error:', error);
        if (mounted) {
          setIsLoading(false);
        }
      }
    };

    // Load Google Maps API
    const loadGoogleMaps = () => {
      // Check if Google Maps is already loaded
      if (window.google && window.google.maps) {
        console.log('🗺️ Google Maps API already loaded');
        initializeMap();
        return;
      }

      // Check if script is already loading
      const existingScript = document.querySelector('script[src*="maps.googleapis.com"]');
      if (existingScript) {
        console.log('🗺️ Google Maps script already exists, waiting...');
        
        const checkInterval = setInterval(() => {
          if (window.google && window.google.maps) {
            clearInterval(checkInterval);
            initializeMap();
          }
        }, 200);
        
        setTimeout(() => {
          clearInterval(checkInterval);
          if (!window.google || !window.google.maps) {
            setIsLoading(false);
          }
        }, 15000);
        return;
      }

      // Create new script
      console.log('🗺️ Loading Google Maps API...');
      const script = document.createElement('script');
      
      // Use environment API key or load basic version
      const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
      if (apiKey && apiKey !== 'AIzaSyD_your_api_key_here') {
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
      } else {
        // Load without key for basic functionality (development only)
        console.warn('🚨 Loading Google Maps without API key (development mode)');
        script.src = 'https://maps.googleapis.com/maps/api/js?libraries=places';
      }
      
      script.async = true;
      script.defer = true;
      
      script.onload = () => {
        console.log('✅ Google Maps API loaded');
        initializeMap();
      };
      
      script.onerror = () => {
        console.error('❌ Failed to load Google Maps API');
        if (mounted) {
          setIsLoading(false);
        }
      };
      
      document.head.appendChild(script);
    };

    // Start loading with small delay
    const timer = setTimeout(loadGoogleMaps, 300);

    return () => {
      mounted = false;
      clearTimeout(timer);
    };
  }, [onLocationSelect]);

  // GPS Detection
  const handleGpsDetection = () => {
    if (!navigator.geolocation) {
      alert('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsGpsLoading(true);
    console.log('🛰️ Starting GPS detection...');

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = parseFloat(position.coords.latitude.toFixed(8));
        const lng = parseFloat(position.coords.longitude.toFixed(8));
        const accuracy = Math.round(position.coords.accuracy);

        console.log(`🎯 GPS detected: ${lat}, ${lng} (±${accuracy}m)`);

        setCurrentLocation({ lat, lng });
        setIsGpsLoading(false);

        // Move map and marker if available
        if (googleMap && marker) {
          const newPosition = { lat, lng };
          googleMap.setCenter(newPosition);
          googleMap.setZoom(18);
          marker.setPosition(newPosition);
        }

        // Call callback
        if (onLocationSelect) {
          onLocationSelect({ lat, lng, accuracy });
        }

        alert(`✅ Lokasi berhasil dideteksi!\n📍 ${lat}, ${lng}\n📏 Akurasi: ${accuracy} meter`);
      },
      (error) => {
        console.error('❌ GPS detection failed:', error);
        setIsGpsLoading(false);
        
        let message = '❌ Gagal mendeteksi lokasi GPS';
        switch(error.code) {
          case 1: message = '🚫 Akses lokasi ditolak oleh pengguna'; break;
          case 2: message = '📡 Sinyal GPS tidak tersedia'; break;
          case 3: message = '⏱️ Waktu deteksi GPS habis'; break;
        }
        
        alert(message);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 60000
      }
    );
  };

  return (
    <div className="space-y-4">
      {/* GPS Button */}
      <Button 
        onClick={handleGpsDetection}
        disabled={isGpsLoading}
        className="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-lg"
        size="lg"
      >
        {isGpsLoading ? (
          <>
            <Loader2 className="w-5 h-5 mr-2 animate-spin" />
            Mendeteksi GPS...
          </>
        ) : (
          <>
            <Navigation className="w-5 h-5 mr-2" />
            Deteksi Lokasi GPS Otomatis
          </>
        )}
      </Button>

      {/* API Key Warning */}
      {hasApiKeyError && (
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-amber-600 mt-0.5" />
            <div className="flex-1">
              <h4 className="font-semibold text-amber-800 mb-2">API Key Diperlukan untuk Produksi</h4>
              <p className="text-sm text-amber-700 mb-3">
                Maps berfungsi dalam mode pengembangan, tetapi memerlukan API key valid untuk produksi.
              </p>
              <Button 
                variant="outline" 
                size="sm" 
                className="border-amber-300 text-amber-700 hover:bg-amber-100"
                onClick={() => window.open('https://developers.google.com/maps/documentation/javascript/get-api-key', '_blank')}
              >
                <ExternalLink className="w-4 h-4 mr-2" />
                Dapatkan API Key Gratis
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Map Container */}
      <div className="relative rounded-xl overflow-hidden shadow-xl border-2 border-gray-200">
        {isLoading && (
          <div 
            className="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center z-10"
            style={{ height }}
          >
            <Loader2 className="w-8 h-8 animate-spin text-blue-600 mb-3" />
            <p className="text-sm font-medium text-gray-700">Memuat Google Maps...</p>
            <p className="text-xs text-gray-500 mt-1">Mohon tunggu sebentar</p>
          </div>
        )}
        
        <div 
          ref={mapRef} 
          style={{ height, width: '100%' }}
          className={`transition-opacity duration-500 ${isLoading ? 'opacity-0' : 'opacity-100'}`}
        />

        {mapReady && !isLoading && (
          <div className="absolute top-4 left-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium flex items-center shadow-lg">
            <CheckCircle className="w-3 h-3 mr-1" />
            Peta Siap
          </div>
        )}
      </div>

      {/* Location Info */}
      {currentLocation && (
        <div className="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-xl border border-green-200">
          <div className="flex items-center gap-2 mb-2">
            <MapPin className="w-5 h-5 text-green-600" />
            <span className="font-semibold text-green-800">📍 Lokasi Terpilih</span>
          </div>
          <div className="text-sm text-green-700 space-y-1">
            <p><strong>Latitude:</strong> {currentLocation.lat.toFixed(8)}</p>
            <p><strong>Longitude:</strong> {currentLocation.lng.toFixed(8)}</p>
            <p className="text-xs text-green-600 mt-2">
              ✅ Koordinat berhasil disimpan dan siap untuk presensi
            </p>
          </div>
        </div>
      )}

      {/* Instructions */}
      <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
        <p className="text-sm text-blue-700 text-center">
          💡 <strong>Cara menggunakan:</strong><br />
          • Klik tombol GPS untuk deteksi otomatis<br />
          • Atau klik pada peta untuk pilih lokasi manual<br />
          • Seret marker untuk penyesuaian presisi
        </p>
      </div>
    </div>
  );
}

export default TestGoogleMap;