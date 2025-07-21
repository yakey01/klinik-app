import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2, CheckCircle, AlertCircle, ExternalLink } from 'lucide-react';

// Leaflet types
declare global {
  interface Window {
    L: any;
  }
}

interface OpenStreetMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
}

export function OpenStreetMap({ onLocationSelect, height = '300px' }: OpenStreetMapProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const leafletMapRef = useRef<any>(null);
  const markerRef = useRef<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [mapReady, setMapReady] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Initialize OpenStreetMap with Leaflet
  useEffect(() => {
    let mounted = true;

    const initializeMap = async () => {
      if (!mounted || !mapRef.current) {
        return;
      }

      try {
        console.log('🗺️ Initializing OpenStreetMap with Leaflet...');
        
        // Check if Leaflet is available
        if (!window.L) {
          throw new Error('Leaflet library not loaded');
        }

        const L = window.L;

        // Create map
        const map = L.map(mapRef.current, {
          center: [-7.808758, 111.962646], // User's coordinates
          zoom: 16,
          zoomControl: true,
          scrollWheelZoom: true,
          doubleClickZoom: true,
          boxZoom: true,
          keyboard: true,
          dragging: true,
          touchZoom: true
        });

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
          maxZoom: 19
        }).addTo(map);

        // Create marker
        const marker = L.marker([-7.808758, 111.962646], {
          draggable: true
        }).addTo(map);

        // Add click listener to map
        map.on('click', function(e: any) {
          if (!mounted) return;
          
          const lat = parseFloat(e.latlng.lat.toFixed(8));
          const lng = parseFloat(e.latlng.lng.toFixed(8));
          
          marker.setLatLng([lat, lng]);
          setCurrentLocation({ lat, lng });
          
          if (onLocationSelect) {
            onLocationSelect({ lat, lng });
          }
          
          console.log(`📍 Map clicked: ${lat}, ${lng}`);
        });

        // Add drag listener to marker
        marker.on('dragend', function() {
          if (!mounted) return;
          
          const position = marker.getLatLng();
          const lat = parseFloat(position.lat.toFixed(8));
          const lng = parseFloat(position.lng.toFixed(8));
          
          setCurrentLocation({ lat, lng });
          
          if (onLocationSelect) {
            onLocationSelect({ lat, lng });
          }
          
          console.log(`🎯 Marker dragged: ${lat}, ${lng}`);
        });

        // Set initial location
        setCurrentLocation({ lat: -7.808758, lng: 111.962646 });
        if (onLocationSelect) {
          onLocationSelect({ lat: -7.808758, lng: 111.962646 });
        }

        // Store references
        leafletMapRef.current = map;
        markerRef.current = marker;

        // Wait for map to be ready and ensure proper rendering
        map.whenReady(() => {
          if (mounted) {
            setTimeout(() => {
              map.invalidateSize(); // Ensure proper rendering
              setMapReady(true);
              setIsLoading(false);
              setError(null);
              console.log('✅ OpenStreetMap initialized successfully!');
            }, 200);
          }
        });

      } catch (error) {
        console.error('❌ Map initialization error:', error);
        if (mounted) {
          setError(error instanceof Error ? error.message : 'Failed to load map');
          setIsLoading(false);
        }
      }
    };

    // Load Leaflet library
    const loadLeaflet = () => {
      // Check if Leaflet is already loaded
      if (window.L) {
        console.log('🗺️ Leaflet already loaded');
        initializeMap();
        return;
      }

      // Check if script is already loading
      const existingScript = document.querySelector('script[src*="leaflet"]');
      if (existingScript) {
        console.log('🗺️ Leaflet script already loading, waiting...');
        
        const checkInterval = setInterval(() => {
          if (window.L) {
            clearInterval(checkInterval);
            initializeMap();
          }
        }, 200);
        
        setTimeout(() => {
          clearInterval(checkInterval);
          if (!window.L) {
            console.error('❌ Leaflet loading timeout');
            if (mounted) {
              setError('Map library loading timeout');
              setIsLoading(false);
            }
          }
        }, 10000);
        return;
      }

      console.log('🗺️ Loading Leaflet library...');

      // Load Leaflet CSS first
      const cssLink = document.createElement('link');
      cssLink.rel = 'stylesheet';
      cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      cssLink.crossOrigin = '';
      document.head.appendChild(cssLink);

      // Load Leaflet JavaScript
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      script.crossOrigin = '';
      script.async = false; // Changed to false for better loading
      script.defer = false; // Changed to false for better loading
      
      script.onload = () => {
        console.log('✅ Leaflet library loaded successfully');
        setTimeout(() => {
          if (window.L && mounted) {
            initializeMap();
          } else if (mounted) {
            setError('Leaflet loaded but not available');
            setIsLoading(false);
          }
        }, 100);
      };
      
      script.onerror = () => {
        console.error('❌ Failed to load Leaflet library');
        if (mounted) {
          setError('Failed to load Leaflet library from CDN');
          setIsLoading(false);
        }
      };
      
      document.head.appendChild(script);
    };

    // Start loading with small delay
    const timer = setTimeout(loadLeaflet, 300);

    return () => {
      mounted = false;
      clearTimeout(timer);
      
      // Cleanup map
      if (leafletMapRef.current) {
        leafletMapRef.current.remove();
        leafletMapRef.current = null;
      }
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
        if (leafletMapRef.current && markerRef.current && window.L) {
          leafletMapRef.current.setView([lat, lng], 18);
          markerRef.current.setLatLng([lat, lng]);
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

  if (error) {
    return (
      <div className="space-y-4">
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

        <div 
          className="relative rounded-xl overflow-hidden shadow-xl border-2 border-red-200 bg-red-50 flex items-center justify-center"
          style={{ height }}
        >
          <div className="text-center p-6">
            <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-red-700 mb-2">Gagal Memuat Peta</h3>
            <p className="text-sm text-red-600 mb-4">{error}</p>
            <Button 
              onClick={() => window.location.reload()} 
              variant="outline"
              className="border-red-300 text-red-700 hover:bg-red-100"
            >
              Muat Ulang Halaman
            </Button>
          </div>
        </div>

        {currentLocation && (
          <div className="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-xl border border-green-200">
            <div className="flex items-center gap-2 mb-2">
              <MapPin className="w-5 h-5 text-green-600" />
              <span className="font-semibold text-green-800">📍 Lokasi GPS Terdeteksi</span>
            </div>
            <div className="text-sm text-green-700 space-y-1">
              <p><strong>Latitude:</strong> {currentLocation.lat.toFixed(8)}</p>
              <p><strong>Longitude:</strong> {currentLocation.lng.toFixed(8)}</p>
            </div>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* GPS Button */}
      <Button 
        onClick={handleGpsDetection}
        disabled={isGpsLoading}
        className="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white shadow-lg"
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

      {/* OpenStreetMap Info */}
      <div className="bg-green-50 border border-green-200 rounded-lg p-3">
        <div className="flex items-center gap-2">
          <CheckCircle className="w-4 h-4 text-green-600" />
          <span className="text-sm font-semibold text-green-800">Menggunakan OpenStreetMap</span>
        </div>
        <p className="text-xs text-green-700 mt-1">
          Peta bebas dan open source tanpa API key • Data © OpenStreetMap contributors
        </p>
      </div>

      {/* Map Container */}
      <div className="relative rounded-xl overflow-hidden shadow-xl border-2 border-gray-200">
        {isLoading && (
          <div 
            className="absolute inset-0 bg-gradient-to-br from-green-50 to-green-100 flex flex-col items-center justify-center z-10"
            style={{ height }}
          >
            <Loader2 className="w-8 h-8 animate-spin text-green-600 mb-3" />
            <p className="text-sm font-medium text-gray-700">Memuat OpenStreetMap...</p>
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
      <div className="bg-green-50 p-3 rounded-lg border border-green-200">
        <p className="text-sm text-green-700 text-center">
          💡 <strong>Cara menggunakan:</strong><br />
          • Klik tombol GPS untuk deteksi otomatis<br />
          • Atau klik pada peta untuk pilih lokasi manual<br />
          • Seret marker hijau untuk penyesuaian presisi
        </p>
      </div>
    </div>
  );
}

export default OpenStreetMap;