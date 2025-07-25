import { useEffect, useRef, useState } from 'react';
import { MapPin, Navigation, Loader2, AlertCircle } from 'lucide-react';
import { Button } from '../ui/button';
import { Alert, AlertDescription } from '../ui/alert';

interface EnhancedLeafletMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
  workLocation?: { lat: number; lng: number; radius: number; name: string };
  requireGPS?: boolean;
}

declare global {
  interface Window {
    L: any;
  }
}

export function EnhancedLeafletMap({ 
  onLocationSelect, 
  height = '400px',
  workLocation,
  requireGPS = true
}: EnhancedLeafletMapProps) {
  const mapRef = useRef<any>(null);
  const markerRef = useRef<any>(null);
  const workLocationMarkerRef = useRef<any>(null);
  const circleRef = useRef<any>(null);
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isDetectingGPS, setIsDetectingGPS] = useState(false);
  const [currentCoords, setCurrentCoords] = useState<{lat: number; lng: number; accuracy?: number} | null>(null);
  const [gpsError, setGpsError] = useState<string | null>(null);
  const [hasGPSLocation, setHasGPSLocation] = useState(false);
  const [mapId] = useState(`leaflet-map-${Math.random().toString(36).substr(2, 9)}`);

  // Load Leaflet CSS and JS
  useEffect(() => {
    const loadLeaflet = async () => {
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

    // Create map centered on work location or default
    const centerLat = workLocation?.lat || -7.8167;
    const centerLng = workLocation?.lng || 112.0167;
    
    const map = L.map(mapContainerRef.current, {
      center: [centerLat, centerLng],
      zoom: 15,
      zoomControl: true,
      attributionControl: true
    });

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
      subdomains: ['a', 'b', 'c']
    }).addTo(map);

    // Add work location marker and circle if provided
    if (workLocation) {
      // Work location marker (red)
      const workIcon = L.divIcon({
        html: `<div class="bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold shadow-lg">W</div>`,
        className: 'custom-div-icon',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
      });

      const workMarker = L.marker([workLocation.lat, workLocation.lng], {
        icon: workIcon,
        draggable: false
      }).addTo(map);
      
      workMarker.bindPopup(`<strong>${workLocation.name}</strong><br>Lokasi Kerja<br>Radius: ${workLocation.radius}m`);
      
      // Add circle for geofence
      const circle = L.circle([workLocation.lat, workLocation.lng], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.1,
        radius: workLocation.radius
      }).addTo(map);

      workLocationMarkerRef.current = workMarker;
      circleRef.current = circle;
    }

    // Store map reference
    mapRef.current = map;

    // Auto-detect GPS on mount
    setTimeout(() => {
      detectGPSLocation();
    }, 500);

    // Cleanup
    return () => {
      if (mapRef.current) {
        mapRef.current.remove();
        mapRef.current = null;
        markerRef.current = null;
        workLocationMarkerRef.current = null;
        circleRef.current = null;
      }
    };
  }, [isLoading, workLocation]);

  const updateLocation = (lat: number, lng: number, accuracy?: number) => {
    const L = window.L;
    if (!L || !mapRef.current) return;

    setCurrentCoords({ lat, lng, accuracy });
    
    // Remove existing user marker
    if (markerRef.current) {
      markerRef.current.remove();
    }

    // Add new user marker (blue)
    const userIcon = L.divIcon({
      html: `<div class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold shadow-lg">U</div>`,
      className: 'custom-div-icon',
      iconSize: [32, 32],
      iconAnchor: [16, 16]
    });

    const marker = L.marker([lat, lng], {
      icon: userIcon,
      draggable: false
    }).addTo(mapRef.current);

    marker.bindPopup(`<strong>Lokasi Anda</strong><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}<br>Akurasi: ${accuracy ? Math.round(accuracy) + 'm' : 'N/A'}`);
    
    markerRef.current = marker;
    
    // Center map on new location
    mapRef.current.setView([lat, lng], 15);

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

  const detectGPSLocation = () => {
    if (!navigator.geolocation) {
      setGpsError('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsDetectingGPS(true);
    setGpsError(null);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        console.log('🎯 GPS Location detected:', { lat, lng, accuracy });

        updateLocation(lat, lng, accuracy);
        setHasGPSLocation(true);
        setIsDetectingGPS(false);

        // Show success message
        const message = `✅ Lokasi GPS berhasil dideteksi!
📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
📏 Akurasi: ${Math.round(accuracy)} meter`;
        
        alert(message);
      },
      (error) => {
        setIsDetectingGPS(false);
        setHasGPSLocation(false);
        
        let errorMessage = '❌ Gagal mendeteksi lokasi GPS';
        switch(error.code) {
          case 1: 
            errorMessage = '🚫 Akses lokasi ditolak. Silakan izinkan akses lokasi di pengaturan browser.'; 
            break;
          case 2: 
            errorMessage = '📡 Sinyal GPS tidak tersedia. Pastikan GPS aktif.'; 
            break;
          case 3: 
            errorMessage = '⏱️ Waktu deteksi GPS habis. Coba lagi.'; 
            break;
        }
        
        setGpsError(errorMessage);
        alert(errorMessage);
        
        console.error('GPS Error:', error);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0 // Don't use cached position
      }
    );
  };

  return (
    <div className="space-y-3 w-full">
      {/* GPS Requirement Alert */}
      {requireGPS && !hasGPSLocation && (
        <Alert className="border-orange-200 bg-orange-50 dark:bg-orange-950/30 dark:border-orange-800">
          <AlertCircle className="h-4 w-4 text-orange-600 dark:text-orange-400" />
          <AlertDescription className="text-orange-700 dark:text-orange-300">
            <strong>Perhatian:</strong> Anda harus menggunakan lokasi GPS aktual untuk melakukan presensi. 
            Klik tombol "Deteksi GPS" untuk mendapatkan lokasi Anda saat ini.
          </AlertDescription>
        </Alert>
      )}

      {/* GPS Error Alert */}
      {gpsError && (
        <Alert className="border-red-200 bg-red-50 dark:bg-red-950/30 dark:border-red-800">
          <AlertCircle className="h-4 w-4 text-red-600 dark:text-red-400" />
          <AlertDescription className="text-red-700 dark:text-red-300">
            {gpsError}
          </AlertDescription>
        </Alert>
      )}

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
          className={`absolute top-2 left-2 z-[1000] ${
            hasGPSLocation 
              ? 'bg-green-500 hover:bg-green-600 text-white' 
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
          } border border-gray-300 dark:border-gray-600 shadow-md`}
          size="sm"
        >
          {isDetectingGPS ? (
            <>
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              Mencari GPS...
            </>
          ) : hasGPSLocation ? (
            <>
              <Navigation className="w-4 h-4 mr-2" />
              GPS Terdeteksi ✓
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
      {currentCoords && (
        <div className="flex items-center justify-between text-sm">
          <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
            <MapPin className="w-4 h-4" />
            <span>Koordinat GPS:</span>
          </div>
          <span className="font-mono font-medium text-gray-900 dark:text-gray-100">
            {currentCoords.lat.toFixed(6)}, {currentCoords.lng.toFixed(6)}
            {currentCoords.accuracy && (
              <span className="text-xs text-gray-500 ml-2">
                (±{Math.round(currentCoords.accuracy)}m)
              </span>
            )}
          </span>
        </div>
      )}

      {/* Legend */}
      {workLocation && (
        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-2">
          <div className="text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan:</div>
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center font-bold text-xs">W</div>
              <span className="text-gray-600 dark:text-gray-400">Lokasi Kerja</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-xs">U</div>
              <span className="text-gray-600 dark:text-gray-400">Lokasi Anda</span>
            </div>
          </div>
          <div className="text-xs text-gray-500 dark:text-gray-400">
            Area merah menunjukkan radius {workLocation.radius}m dari lokasi kerja
          </div>
        </div>
      )}

      {/* Instructions */}
      <div className="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
        <div className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
          <p className="font-medium mb-1">📍 Panduan Penggunaan:</p>
          <ul className="list-disc list-inside space-y-0.5 text-xs">
            <li className="text-red-600 dark:text-red-400 font-medium">PENTING: Gunakan tombol "Deteksi GPS" untuk mendapatkan lokasi aktual Anda</li>
            <li>Pastikan GPS di perangkat Anda aktif</li>
            <li>Izinkan akses lokasi ketika browser meminta</li>
            <li>Tunggu hingga lokasi terdeteksi dengan akurat</li>
            <li>Anda harus berada dalam radius yang ditentukan dari lokasi kerja</li>
          </ul>
        </div>
      </div>
    </div>
  );
}