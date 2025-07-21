import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2, CheckCircle, AlertCircle } from 'lucide-react';

interface SimpleOpenStreetMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
}

export function SimpleOpenStreetMap({ onLocationSelect, height = '300px' }: SimpleOpenStreetMapProps) {
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [isAutoDetecting, setIsAutoDetecting] = useState(true);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [clickPosition, setClickPosition] = useState<{ x: number; y: number } | null>(null);
  const [locationSource, setLocationSource] = useState<'gps' | 'manual' | 'default'>('default');

  // Auto-detect GPS location on component mount
  useEffect(() => {
    const autoDetectGPS = () => {
      if (navigator.geolocation) {
        console.log('🛰️ Auto-detecting GPS location...');
        
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = parseFloat(position.coords.latitude.toFixed(8));
            const lng = parseFloat(position.coords.longitude.toFixed(8));
            const accuracy = Math.round(position.coords.accuracy);

            console.log(`🎯 Auto GPS detected: ${lat}, ${lng} (±${accuracy}m)`);

            const newLocation = { lat, lng };
            setCurrentLocation(newLocation);
            setClickPosition(null); // Reset click position for GPS
            setLocationSource('gps');
            setIsAutoDetecting(false);

            if (onLocationSelect) {
              onLocationSelect({ lat, lng, accuracy });
            }

            // Show success notification
            console.log(`✅ Lokasi otomatis terdeteksi: ${lat}, ${lng} (±${accuracy}m)`);
          },
          (error) => {
            console.warn('⚠️ Auto GPS detection failed, using default location:', error.message);
            // Fallback to default location if GPS fails
            const defaultLocation = { lat: -7.808758, lng: 111.962646 };
            setCurrentLocation(defaultLocation);
            setLocationSource('default');
            setIsAutoDetecting(false);
            if (onLocationSelect) {
              onLocationSelect(defaultLocation);
            }
          },
          {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000 // 5 minutes cache
          }
        );
      } else {
        console.warn('⚠️ Geolocation not supported, using default location');
        // Fallback if geolocation not supported
        const defaultLocation = { lat: -7.808758, lng: 111.962646 };
        setCurrentLocation(defaultLocation);
        setLocationSource('default');
        setIsAutoDetecting(false);
        if (onLocationSelect) {
          onLocationSelect(defaultLocation);
        }
      }
    };

    // Auto-detect location when component mounts
    autoDetectGPS();
  }, [onLocationSelect]);

  // Handle map click (simulate coordinate selection)
  const handleMapClick = (event: React.MouseEvent<HTMLDivElement>) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    // Convert click position to approximate coordinates (this is a simulation)
    const lat = -7.808758 + (y / rect.height - 0.5) * 0.01; // Small offset based on click position
    const lng = 111.962646 + (x / rect.width - 0.5) * 0.01;
    
    const newLocation = {
      lat: parseFloat(lat.toFixed(8)),
      lng: parseFloat(lng.toFixed(8))
    };
    
    setCurrentLocation(newLocation);
    setClickPosition({ x, y });
    setLocationSource('manual');
    
    if (onLocationSelect) {
      onLocationSelect(newLocation);
    }
    
    console.log(`📍 Map clicked: ${newLocation.lat}, ${newLocation.lng}`);
  };

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

        const newLocation = { lat, lng };
        setCurrentLocation(newLocation);
        setIsGpsLoading(false);
        setLocationSource('gps');
        
        // Reset click position for GPS
        setClickPosition(null);

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
    <div className="space-y-4 w-full max-w-full">
      {/* Current Location Banner - Always visible */}
      {currentLocation && (
        <div className="bg-green-100 border-l-4 border-green-500 p-3 rounded">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-sm font-bold text-green-800">
                📍 Lokasi Saat Ini {locationSource === 'gps' ? '(GPS)' : locationSource === 'manual' ? '(Manual)' : '(Default)'}
              </h3>
              <p className="text-xs text-green-600">
                {currentLocation.lat.toFixed(8)}, {currentLocation.lng.toFixed(8)}
              </p>
            </div>
            <div className={`px-2 py-1 rounded text-xs font-bold ${
              locationSource === 'gps' ? 'bg-green-500 text-white' :
              locationSource === 'manual' ? 'bg-blue-500 text-white' : 'bg-yellow-500 text-white'
            }`}>
              {locationSource === 'gps' ? 'GPS AKTIF' : 
               locationSource === 'manual' ? 'MANUAL' : 'DEFAULT'}
            </div>
          </div>
        </div>
      )}

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
            Deteksi Ulang Lokasi GPS
          </>
        )}
      </Button>

      {/* Location Status Info */}
      {isAutoDetecting ? (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <div className="flex items-center gap-2">
            <Loader2 className="w-4 h-4 text-blue-600 animate-spin" />
            <span className="text-sm font-semibold text-blue-800">Mendeteksi Lokasi Otomatis...</span>
          </div>
          <p className="text-xs text-blue-700 mt-1">
            Meminta izin akses lokasi untuk menunjuk posisi Anda
          </p>
        </div>
      ) : (
        <div className={`border rounded-lg p-3 ${
          locationSource === 'gps' 
            ? 'bg-green-50 border-green-200' 
            : locationSource === 'manual'
            ? 'bg-blue-50 border-blue-200'
            : 'bg-yellow-50 border-yellow-200'
        }`}>
          <div className="flex items-center gap-2">
            {locationSource === 'gps' ? (
              <>
                <CheckCircle className="w-4 h-4 text-green-600" />
                <span className="text-sm font-semibold text-green-800">📍 Lokasi GPS Terdeteksi</span>
              </>
            ) : locationSource === 'manual' ? (
              <>
                <MapPin className="w-4 h-4 text-blue-600" />
                <span className="text-sm font-semibold text-blue-800">📍 Lokasi Dipilih Manual</span>
              </>
            ) : (
              <>
                <AlertCircle className="w-4 h-4 text-yellow-600" />
                <span className="text-sm font-semibold text-yellow-800">📍 Menggunakan Lokasi Default</span>
              </>
            )}
          </div>
          <p className={`text-xs mt-1 ${
            locationSource === 'gps' 
              ? 'text-green-700' 
              : locationSource === 'manual'
              ? 'text-blue-700'
              : 'text-yellow-700'
          }`}>
            {locationSource === 'gps' 
              ? 'Lokasi akurat dari GPS Anda'
              : locationSource === 'manual'
              ? 'Lokasi dipilih dengan klik pada peta'
              : 'GPS tidak tersedia, gunakan tombol GPS untuk coba lagi'
            }
          </p>
        </div>
      )}

      {/* Simple Map Container */}
      <div className="relative rounded-xl overflow-hidden shadow-xl border-2 border-gray-200">
        <div 
          className="relative cursor-crosshair bg-gradient-to-br from-green-100 via-blue-50 to-green-100"
          style={{ height, width: '100%' }}
          onClick={handleMapClick}
        >
          {/* Grid Pattern to simulate map */}
          <div className="absolute inset-0 opacity-20">
            <svg width="100%" height="100%" className="absolute inset-0">
              <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                  <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#10b981" strokeWidth="1"/>
                </pattern>
              </defs>
              <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
          </div>

          {/* Map Title */}
          <div className="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-2 rounded-lg shadow-sm">
            <div className="flex items-center gap-2">
              <MapPin className="w-4 h-4 text-green-600" />
              <span className="text-sm font-semibold text-gray-800">Peta Lokasi</span>
            </div>
          </div>

          {/* GPS Location marker - shows actual GPS position */}
          {locationSource === 'gps' && !clickPosition && (
            <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              <div className="w-8 h-8 bg-green-500 border-3 border-white rounded-full shadow-xl flex items-center justify-center animate-pulse">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
              {/* GPS Accuracy Circle */}
              <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-16 h-16 border-2 border-green-300 rounded-full opacity-50 animate-ping"></div>
              {/* GPS Label */}
              <div className="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">
                GPS
              </div>
            </div>
          )}

          {/* Default center marker - when no GPS */}
          {locationSource !== 'gps' && !clickPosition && (
            <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              <div className="w-6 h-6 bg-yellow-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center">
                <div className="w-2 h-2 bg-white rounded-full"></div>
              </div>
            </div>
          )}

          {/* Click marker */}
          {clickPosition && (
            <div 
              className="absolute transform -translate-x-1/2 -translate-y-1/2"
              style={{ left: clickPosition.x, top: clickPosition.y }}
            >
              <div className="w-8 h-8 bg-blue-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center animate-ping">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
            </div>
          )}

          {/* Coordinates display */}
          <div className="absolute bottom-4 left-4 bg-white/95 backdrop-blur px-3 py-2 rounded-lg shadow-md text-xs font-mono border">
            {currentLocation ? (
              <>
                <div className="font-bold text-green-600">{Math.abs(currentLocation.lat).toFixed(6)}°{currentLocation.lat >= 0 ? 'N' : 'S'}</div>
                <div className="font-bold text-blue-600">{Math.abs(currentLocation.lng).toFixed(6)}°{currentLocation.lng >= 0 ? 'E' : 'W'}</div>
              </>
            ) : (
              <div className="text-gray-400">--.------°, --.------°</div>
            )}
            {locationSource === 'gps' && (
              <div className="text-green-500 text-center font-bold">GPS</div>
            )}
          </div>

          {/* Status indicator */}
          {isAutoDetecting ? (
            <div className="absolute top-4 right-4 bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-medium flex items-center shadow-lg">
              <Loader2 className="w-3 h-3 mr-1 animate-spin" />
              Mencari GPS...
            </div>
          ) : (
            <div className={`absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium flex items-center shadow-lg ${
              locationSource === 'gps' 
                ? 'bg-green-500 text-white'
                : locationSource === 'manual'
                ? 'bg-blue-500 text-white'
                : 'bg-yellow-500 text-white'
            }`}>
              <CheckCircle className="w-3 h-3 mr-1" />
              {locationSource === 'gps' 
                ? 'GPS Aktif'
                : locationSource === 'manual'
                ? 'Manual'
                : 'Default'
              }
            </div>
          )}
        </div>
      </div>

      {/* Location Info */}
      {currentLocation && (
        <div className={`p-4 rounded-xl border ${
          locationSource === 'gps'
            ? 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200'
            : locationSource === 'manual'
            ? 'bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200'
            : 'bg-gradient-to-r from-yellow-50 to-orange-50 border-yellow-200'
        }`}>
          <div className="flex items-center gap-2 mb-2">
            <MapPin className={`w-5 h-5 ${
              locationSource === 'gps' 
                ? 'text-green-600' 
                : locationSource === 'manual' 
                ? 'text-blue-600' 
                : 'text-yellow-600'
            }`} />
            <span className={`font-semibold ${
              locationSource === 'gps' 
                ? 'text-green-800' 
                : locationSource === 'manual' 
                ? 'text-blue-800' 
                : 'text-yellow-800'
            }`}>
              📍 Lokasi Terpilih
              {locationSource === 'gps' && ' (GPS)'}
              {locationSource === 'manual' && ' (Manual)'}
              {locationSource === 'default' && ' (Default)'}
            </span>
          </div>
          <div className={`text-sm space-y-1 ${
            locationSource === 'gps' 
              ? 'text-green-700' 
              : locationSource === 'manual' 
              ? 'text-blue-700' 
              : 'text-yellow-700'
          }`}>
            <p><strong>Latitude:</strong> {currentLocation.lat.toFixed(8)}</p>
            <p><strong>Longitude:</strong> {currentLocation.lng.toFixed(8)}</p>
            <p className={`text-xs mt-2 ${
              locationSource === 'gps' 
                ? 'text-green-600' 
                : locationSource === 'manual' 
                ? 'text-blue-600' 
                : 'text-yellow-600'
            }`}>
              {locationSource === 'gps' 
                ? '✅ Koordinat GPS akurat dan siap untuk presensi'
                : locationSource === 'manual'
                ? '✅ Koordinat manual dan siap untuk presensi'
                : '⚠️ Menggunakan lokasi default, disarankan gunakan GPS'
              }
            </p>
          </div>
        </div>
      )}

      {/* Debug Info - hapus ini setelah testing */}
      <div className="bg-gray-100 p-2 rounded text-xs text-gray-600">
        <p><strong>Debug Info:</strong></p>
        <p>Current Location: {currentLocation ? `${currentLocation.lat}, ${currentLocation.lng}` : 'null'}</p>
        <p>Location Source: {locationSource}</p>
        <p>Auto Detecting: {isAutoDetecting ? 'true' : 'false'}</p>
        <p>GPS Loading: {isGpsLoading ? 'true' : 'false'}</p>
      </div>

      {/* Status Summary */}
      <div className="bg-white border-2 border-gray-200 rounded-lg p-4 shadow-sm">
        <h3 className="font-bold text-gray-800 mb-3">📋 Status Lengkap</h3>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-gray-600">Status GPS:</span>
            <span className={`ml-2 font-bold ${
              locationSource === 'gps' ? 'text-green-600' : 
              locationSource === 'manual' ? 'text-blue-600' : 'text-yellow-600'
            }`}>
              {locationSource === 'gps' ? '✅ AKTIF' : 
               locationSource === 'manual' ? '🔵 MANUAL' : '⚠️ DEFAULT'}
            </span>
          </div>
          <div>
            <span className="text-gray-600">Koordinat:</span>
            <span className="ml-2 font-mono text-xs">
              {currentLocation ? '✅ ADA' : '❌ KOSONG'}
            </span>
          </div>
          <div>
            <span className="text-gray-600">Auto Detect:</span>
            <span className="ml-2 font-bold">
              {isAutoDetecting ? '🔄 PROSES' : '✅ SELESAI'}
            </span>
          </div>
          <div>
            <span className="text-gray-600">Siap Presensi:</span>
            <span className="ml-2 font-bold text-green-600">
              {currentLocation ? '✅ YA' : '❌ TIDAK'}
            </span>
          </div>
        </div>
        
        {currentLocation && (
          <div className="mt-3 p-2 bg-gray-50 rounded text-xs font-mono">
            <strong>Koordinat Detail:</strong><br />
            Latitude: {currentLocation.lat.toFixed(8)}<br />
            Longitude: {currentLocation.lng.toFixed(8)}
          </div>
        )}
      </div>

      {/* Instructions */}
      <div className="bg-green-50 p-3 rounded-lg border border-green-200">
        <p className="text-sm text-green-700 text-center">
          💡 <strong>Cara menggunakan:</strong><br />
          • Lokasi otomatis terdeteksi saat pertama kali buka<br />
          • Klik "Deteksi Ulang" jika perlu update lokasi<br />
          • Klik pada area peta untuk pilih lokasi manual<br />
          • GPS memberikan koordinat yang paling akurat
        </p>
      </div>
    </div>
  );
}

export default SimpleOpenStreetMap;