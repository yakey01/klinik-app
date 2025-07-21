import { useEffect, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2, CheckCircle, AlertCircle } from 'lucide-react';

interface ReliableMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
}

export function ReliableMap({ onLocationSelect, height = '300px' }: ReliableMapProps) {
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [isAutoDetecting, setIsAutoDetecting] = useState(true);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [locationSource, setLocationSource] = useState<'gps' | 'manual' | 'default'>('default');

  // Auto-detect GPS location on component mount
  useEffect(() => {
    const autoDetectGPS = () => {
      if (navigator.geolocation) {
        // Auto-detecting GPS location
        
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = parseFloat(position.coords.latitude.toFixed(8));
            const lng = parseFloat(position.coords.longitude.toFixed(8));
            const accuracy = Math.round(position.coords.accuracy);

            // Auto GPS detected

            const newLocation = { lat, lng };
            setCurrentLocation(newLocation);
            setLocationSource('gps');
            setIsAutoDetecting(false);

            if (onLocationSelect) {
              onLocationSelect({ lat, lng, accuracy });
            }

            // Lokasi otomatis terdeteksi
          },
          (error) => {
            // Auto GPS detection failed, using default location
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
            maximumAge: 300000
          }
        );
      } else {
        // Geolocation not supported, using default location
        const defaultLocation = { lat: -7.808758, lng: 111.962646 };
        setCurrentLocation(defaultLocation);
        setLocationSource('default');
        setIsAutoDetecting(false);
        if (onLocationSelect) {
          onLocationSelect(defaultLocation);
        }
      }
    };

    autoDetectGPS();
  }, [onLocationSelect]);

  // GPS Detection (manual)
  const handleGpsDetection = () => {
    if (!navigator.geolocation) {
      alert('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsGpsLoading(true);
    // Starting manual GPS detection

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = parseFloat(position.coords.latitude.toFixed(8));
        const lng = parseFloat(position.coords.longitude.toFixed(8));
        const accuracy = Math.round(position.coords.accuracy);

        // Manual GPS detected

        const newLocation = { lat, lng };
        setCurrentLocation(newLocation);
        setIsGpsLoading(false);
        setLocationSource('gps');

        if (onLocationSelect) {
          onLocationSelect({ lat, lng, accuracy });
        }

        alert(`✅ Lokasi berhasil dideteksi!\n📍 ${lat}, ${lng}\n📏 Akurasi: ${accuracy} meter`);
      },
      (error) => {
        // GPS detection failed
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
    <div className="space-y-4 w-full">
      {/* Current Location Status - ALWAYS VISIBLE */}
      <div className={`p-4 rounded-xl border-2 shadow-lg ${
        locationSource === 'gps' 
          ? 'bg-green-50 border-green-300' 
          : locationSource === 'manual' 
          ? 'bg-blue-50 border-blue-300' 
          : 'bg-yellow-50 border-yellow-300'
      }`}>
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <MapPin className={`w-6 h-6 ${
              locationSource === 'gps' ? 'text-green-600' : 
              locationSource === 'manual' ? 'text-blue-600' : 'text-yellow-600'
            }`} />
            <h2 className={`text-lg font-bold ${
              locationSource === 'gps' ? 'text-green-800' : 
              locationSource === 'manual' ? 'text-blue-800' : 'text-yellow-800'
            }`}>
              📍 LOKASI SAAT INI
            </h2>
          </div>
          <div className={`px-3 py-1 rounded-full text-sm font-bold ${
            locationSource === 'gps' ? 'bg-green-500 text-white' :
            locationSource === 'manual' ? 'bg-blue-500 text-white' : 'bg-yellow-500 text-white'
          }`}>
            {locationSource === 'gps' ? '🛰️ GPS AKTIF' : 
             locationSource === 'manual' ? '👆 MANUAL' : '📍 DEFAULT'}
          </div>
        </div>

        {currentLocation && (
          <div className="bg-white p-3 rounded-lg shadow-sm">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-600">Latitude:</span>
                <div className="font-mono text-lg font-bold text-green-600">
                  {currentLocation.lat.toFixed(8)}°
                </div>
              </div>
              <div>
                <span className="text-sm text-gray-600">Longitude:</span>
                <div className="font-mono text-lg font-bold text-blue-600">
                  {currentLocation.lng.toFixed(8)}°
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* GPS Detection Button */}
      <Button 
        onClick={handleGpsDetection}
        disabled={isGpsLoading}
        className="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white shadow-lg h-14"
        size="lg"
      >
        {isGpsLoading ? (
          <>
            <Loader2 className="w-6 h-6 mr-3 animate-spin" />
            Mendeteksi GPS...
          </>
        ) : (
          <>
            <Navigation className="w-6 h-6 mr-3" />
            {locationSource === 'gps' ? 'Perbarui Lokasi GPS' : 'Deteksi Lokasi GPS'}
          </>
        )}
      </Button>

      {/* Visual Map Representation */}
      <div className="bg-white rounded-xl border-2 border-gray-200 shadow-lg overflow-hidden">
        <div className="bg-gradient-to-r from-blue-500 to-green-500 text-white p-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <MapPin className="w-5 h-5" />
              <span className="font-semibold">Peta Lokasi</span>
            </div>
            <div className={`px-2 py-1 rounded text-xs font-bold ${
              isAutoDetecting ? 'bg-blue-600' :
              locationSource === 'gps' ? 'bg-green-600' : 
              locationSource === 'manual' ? 'bg-blue-600' : 'bg-yellow-600'
            }`}>
              {isAutoDetecting ? '🔄 LOADING' :
               locationSource === 'gps' ? '🛰️ GPS' : 
               locationSource === 'manual' ? '👆 MANUAL' : '📍 DEFAULT'}
            </div>
          </div>
        </div>

        {/* Map Area */}
        <div 
          className="relative bg-gradient-to-br from-green-100 via-blue-50 to-green-100"
          style={{ height }}
        >
          {/* Grid Background */}
          <div className="absolute inset-0 opacity-30">
            <div className="w-full h-full" style={{
              backgroundImage: `
                linear-gradient(to right, #10b981 1px, transparent 1px),
                linear-gradient(to bottom, #10b981 1px, transparent 1px)
              `,
              backgroundSize: '30px 30px'
            }}></div>
          </div>

          {/* Location Marker */}
          <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10">
            <div className={`relative ${
              locationSource === 'gps' ? 'animate-pulse' : ''
            }`}>
              {/* Main marker */}
              <div className={`w-12 h-12 rounded-full border-4 border-white shadow-xl flex items-center justify-center ${
                locationSource === 'gps' ? 'bg-green-500' :
                locationSource === 'manual' ? 'bg-blue-500' : 'bg-yellow-500'
              }`}>
                <div className="w-4 h-4 bg-white rounded-full"></div>
              </div>
              
              {/* Accuracy ring for GPS */}
              {locationSource === 'gps' && (
                <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-24 h-24 border-2 border-green-300 rounded-full opacity-60 animate-ping"></div>
              )}
              
              {/* Label */}
              <div className={`absolute -bottom-10 left-1/2 transform -translate-x-1/2 px-2 py-1 rounded text-xs font-bold text-white shadow-lg ${
                locationSource === 'gps' ? 'bg-green-500' :
                locationSource === 'manual' ? 'bg-blue-500' : 'bg-yellow-500'
              }`}>
                {locationSource === 'gps' ? '🛰️ GPS' :
                 locationSource === 'manual' ? '👆 MANUAL' : '📍 DEFAULT'}
              </div>
            </div>
          </div>

          {/* Coordinates Display */}
          {currentLocation && (
            <div className="absolute bottom-4 left-4 bg-white/95 backdrop-blur border border-gray-200 rounded-lg p-2 shadow-lg">
              <div className="text-xs font-mono">
                <div className="text-green-600 font-bold">
                  {Math.abs(currentLocation.lat).toFixed(6)}°{currentLocation.lat >= 0 ? 'N' : 'S'}
                </div>
                <div className="text-blue-600 font-bold">
                  {Math.abs(currentLocation.lng).toFixed(6)}°{currentLocation.lng >= 0 ? 'E' : 'W'}
                </div>
              </div>
            </div>
          )}

          {/* Loading indicator */}
          {isAutoDetecting && (
            <div className="absolute inset-0 bg-blue-500/20 flex items-center justify-center">
              <div className="bg-white rounded-lg p-4 shadow-lg flex items-center gap-3">
                <Loader2 className="w-6 h-6 text-blue-600 animate-spin" />
                <span className="text-blue-800 font-semibold">Mencari lokasi GPS...</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Status Grid */}
      <div className="grid grid-cols-2 gap-4">
        <div className="bg-white rounded-lg border border-gray-200 p-3 text-center">
          <div className={`text-2xl font-bold ${
            locationSource === 'gps' ? 'text-green-600' : 'text-gray-400'
          }`}>
            {locationSource === 'gps' ? '✅' : '❌'}
          </div>
          <div className="text-sm font-semibold text-gray-700">GPS Status</div>
          <div className="text-xs text-gray-500">
            {locationSource === 'gps' ? 'Aktif' : 'Tidak aktif'}
          </div>
        </div>

        <div className="bg-white rounded-lg border border-gray-200 p-3 text-center">
          <div className={`text-2xl font-bold ${
            currentLocation ? 'text-green-600' : 'text-gray-400'
          }`}>
            {currentLocation ? '📍' : '❌'}
          </div>
          <div className="text-sm font-semibold text-gray-700">Koordinat</div>
          <div className="text-xs text-gray-500">
            {currentLocation ? 'Tersedia' : 'Belum ada'}
          </div>
        </div>
      </div>

      {/* Instructions */}
      <div className="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4">
        <div className="flex items-center gap-2 mb-2">
          <CheckCircle className="w-5 h-5 text-green-600" />
          <span className="font-semibold text-green-800">Cara Menggunakan</span>
        </div>
        <div className="text-sm text-green-700 space-y-1">
          <p>• Lokasi otomatis terdeteksi saat buka aplikasi</p>
          <p>• Klik tombol hijau untuk perbarui/deteksi ulang GPS</p>
          <p>• Pastikan GPS aktif di browser untuk akurasi terbaik</p>
          <p>• Koordinat siap digunakan untuk presensi</p>
        </div>
      </div>
    </div>
  );
}

export default ReliableMap;