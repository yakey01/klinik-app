import { useEffect, useRef, useState } from 'react';
import { MapPin, Navigation, Loader2 } from 'lucide-react';
import { Button } from '../ui/button';

interface LeafletMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
  defaultLat?: number;
  defaultLng?: number;
  defaultZoom?: number;
}

declare global {
  interface Window {
    L: any;
  }
}

export function LeafletMap({ 
  onLocationSelect, 
  height = '400px',
  defaultLat = -7.808758,
  defaultLng = 111.962646,
  defaultZoom = 15
}: LeafletMapProps) {
  const mapRef = useRef<any>(null);
  const markerRef = useRef<any>(null);
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isDetectingGPS, setIsDetectingGPS] = useState(false);
  const [currentCoords, setCurrentCoords] = useState({ lat: defaultLat, lng: defaultLng });
  const [mapId] = useState(`leaflet-map-${Math.random().toString(36).substr(2, 9)}`);

  // Load Leaflet CSS and JS
  useEffect(() => {
    const loadLeaflet = async () => {
      // Check if already loaded
      if (window.L) {
        setIsLoading(false);
        return;
      }

      // Load CSS
      if (!document.querySelector('link[href*="leaflet"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
        link.crossOrigin = '';
        document.head.appendChild(link);
      }

      // Load JS
      if (!document.querySelector('script[src*="leaflet"]')) {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
        script.crossOrigin = '';
        script.onload = () => setIsLoading(false);
        document.head.appendChild(script);
      } else {
        setIsLoading(false);
      }
    };

    loadLeaflet();
  }, []);

  // Initialize map
  useEffect(() => {
    if (isLoading || !mapContainerRef.current || mapRef.current) return;

    const L = window.L;
    if (!L) return;

    // Create map
    const map = L.map(mapContainerRef.current, {
      center: [defaultLat, defaultLng],
      zoom: defaultZoom,
      zoomControl: true,
      attributionControl: true
    });

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
      subdomains: ['a', 'b', 'c']
    }).addTo(map);

    // Add marker
    const marker = L.marker([defaultLat, defaultLng], {
      draggable: true
    }).addTo(map);

    // Store references
    mapRef.current = map;
    markerRef.current = marker;

    // Event handlers
    marker.on('dragend', (e: any) => {
      const pos = e.target.getLatLng();
      updateLocation(pos.lat, pos.lng);
    });

    map.on('click', (e: any) => {
      marker.setLatLng(e.latlng);
      updateLocation(e.latlng.lat, e.latlng.lng);
    });

    // Auto-detect GPS on mount
    setTimeout(() => {
      detectGPSLocation(true); // silent auto-detect
    }, 500);

    // Cleanup
    return () => {
      if (mapRef.current) {
        mapRef.current.remove();
        mapRef.current = null;
        markerRef.current = null;
      }
    };
  }, [isLoading, defaultLat, defaultLng, defaultZoom]);

  const updateLocation = (lat: number, lng: number, accuracy?: number) => {
    setCurrentCoords({ lat, lng });
    
    if (onLocationSelect) {
      // Get address using Nominatim reverse geocoding
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(res => res.json())
        .then(data => {
          const address = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
          onLocationSelect({ lat, lng, accuracy, address });
        })
        .catch(() => {
          onLocationSelect({ lat, lng, accuracy });
        });
    }
  };

  const detectGPSLocation = (silent = false) => {
    if (!navigator.geolocation) {
      if (!silent) alert('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsDetectingGPS(true);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        if (mapRef.current && markerRef.current) {
          mapRef.current.setView([lat, lng], defaultZoom);
          markerRef.current.setLatLng([lat, lng]);
        }

        updateLocation(lat, lng, accuracy);
        setIsDetectingGPS(false);

        if (!silent) {
          alert(`✅ Lokasi berhasil dideteksi!\n📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}\n📏 Akurasi: ${Math.round(accuracy)} meter`);
        }
      },
      (error) => {
        setIsDetectingGPS(false);
        
        if (!silent) {
          let message = '❌ Gagal mendeteksi lokasi GPS';
          switch(error.code) {
            case 1: message = '🚫 Akses lokasi ditolak oleh pengguna'; break;
            case 2: message = '📡 Sinyal GPS tidak tersedia'; break;
            case 3: message = '⏱️ Waktu deteksi GPS habis'; break;
          }
          alert(message);
        }
      },
      {
        enableHighAccuracy: true,
        timeout: silent ? 8000 : 15000,
        maximumAge: 300000 // 5 minutes cache
      }
    );
  };

  return (
    <div className="space-y-3 w-full">
      {/* Map Container */}
      <div className="relative border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
        {isLoading && (
          <div className="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-50">
            <div className="flex items-center gap-2">
              <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
              <span className="text-gray-700 dark:text-gray-300">Loading map...</span>
            </div>
          </div>
        )}
        
        <div 
          ref={mapContainerRef}
          id={mapId}
          style={{ height, width: '100%', minHeight: '300px' }}
          className="z-0"
        />
        
        {/* GPS Detection Button */}
        <Button
          onClick={() => detectGPSLocation()}
          disabled={isDetectingGPS}
          className="absolute top-2 left-2 z-[1000] bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 shadow-md"
          size="sm"
        >
          {isDetectingGPS ? (
            <>
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              Mencari...
            </>
          ) : (
            <>
              <Navigation className="w-4 h-4 mr-2" />
              Deteksi GPS
            </>
          )}
        </Button>
      </div>

      {/* Coordinates Display */}
      <div className="flex items-center justify-between text-sm">
        <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <MapPin className="w-4 h-4" />
          <span>Koordinat:</span>
        </div>
        <span className="font-mono font-medium text-gray-900 dark:text-gray-100">
          {currentCoords.lat.toFixed(6)}, {currentCoords.lng.toFixed(6)}
        </span>
      </div>

      {/* Instructions */}
      <div className="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
        <div className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
          <p className="font-medium mb-1">📍 Cara Menggunakan:</p>
          <ul className="list-disc list-inside space-y-0.5 text-xs">
            <li>Klik pada peta untuk memilih lokasi</li>
            <li>Seret marker untuk mengubah posisi</li>
            <li>Gunakan tombol "Deteksi GPS" untuk lokasi saat ini</li>
            <li>Scroll untuk zoom in/out</li>
          </ul>
        </div>
      </div>
    </div>
  );
}

export default LeafletMap;