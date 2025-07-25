import React, { useState, useEffect, useRef } from 'react';
import { MapPin, Navigation, AlertCircle, CheckCircle, Clock, MapIcon } from 'lucide-react';

interface WorkLocation {
  id: number;
  name: string;
  description?: string;
  latitude: number;
  longitude: number;
  radius: number;
  address?: string;
  color: string;
  is_active: boolean;
}

interface UserLocation {
  latitude: number;
  longitude: number;
  accuracy?: number;
  timestamp: number;
}

interface GeofencingMapProps {
  workLocation?: WorkLocation;
  userLocation?: UserLocation;
  onLocationUpdate?: (location: UserLocation) => void;
  showCheckInButton?: boolean;
  onCheckIn?: (location: UserLocation) => void;
  isCheckingIn?: boolean;
}

const GeofencingMap: React.FC<GeofencingMapProps> = ({
  workLocation,
  userLocation,
  onLocationUpdate,
  showCheckInButton = false,
  onCheckIn,
  isCheckingIn = false,
}) => {
  const [currentLocation, setCurrentLocation] = useState<UserLocation | null>(userLocation || null);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [isWithinGeofence, setIsWithinGeofence] = useState<boolean>(false);
  const [distance, setDistance] = useState<number>(0);
  const [isWatchingLocation, setIsWatchingLocation] = useState<boolean>(false);
  const mapRef = useRef<HTMLDivElement>(null);
  const watchId = useRef<number | null>(null);

  // Haversine formula untuk menghitung jarak
  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371000; // Radius bumi dalam meter
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  };

  // Update status geofencing
  useEffect(() => {
    if (workLocation && currentLocation) {
      const dist = calculateDistance(
        workLocation.latitude,
        workLocation.longitude,
        currentLocation.latitude,
        currentLocation.longitude
      );
      
      setDistance(dist);
      
      // Tambahkan toleransi GPS accuracy jika ada
      const effectiveRadius = workLocation.radius + (currentLocation.accuracy || 0);
      setIsWithinGeofence(dist <= effectiveRadius);
    }
  }, [workLocation, currentLocation]);

  // Mulai tracking lokasi GPS
  const startLocationTracking = () => {
    if (!navigator.geolocation) {
      setLocationError('Geolocation tidak didukung oleh browser ini');
      return;
    }

    setIsWatchingLocation(true);
    setLocationError(null);

    const options: PositionOptions = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    };

    watchId.current = navigator.geolocation.watchPosition(
      (position) => {
        const newLocation: UserLocation = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          timestamp: position.timestamp,
        };
        
        setCurrentLocation(newLocation);
        onLocationUpdate?.(newLocation);
      },
      (error) => {
        let errorMessage = 'Gagal mendapatkan lokasi';
        switch(error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = 'Izin akses lokasi ditolak. Mohon aktifkan GPS dan berikan izin.';
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = 'Informasi lokasi tidak tersedia.';
            break;
          case error.TIMEOUT:
            errorMessage = 'Timeout mendapatkan lokasi. Coba lagi.';
            break;
        }
        setLocationError(errorMessage);
        setIsWatchingLocation(false);
      },
      options
    );
  };

  // Hentikan tracking lokasi
  const stopLocationTracking = () => {
    if (watchId.current !== null) {
      navigator.geolocation.clearWatch(watchId.current);
      watchId.current = null;
    }
    setIsWatchingLocation(false);
  };

  // Handle check-in
  const handleCheckIn = () => {
    if (currentLocation && onCheckIn) {
      onCheckIn(currentLocation);
    }
  };

  // Cleanup saat component unmount
  useEffect(() => {
    return () => {
      stopLocationTracking();
    };
  }, []);

  return (
    <div className="bg-white rounded-xl shadow-lg overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4">
        <div className="flex items-center space-x-2">
          <MapIcon className="w-6 h-6" />
          <h3 className="text-lg font-semibold">Geofencing Area Kerja</h3>
        </div>
        {workLocation && (
          <p className="text-blue-100 text-sm mt-1">{workLocation.name}</p>
        )}
      </div>

      {/* Location Status */}
      <div className="p-4 border-b border-gray-100">
        {workLocation ? (
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Status Lokasi:</span>
              <div className="flex items-center space-x-2">
                {isWithinGeofence ? (
                  <>
                    <CheckCircle className="w-4 h-4 text-green-500" />
                    <span className="text-green-600 font-medium text-sm">Dalam Area</span>
                  </>
                ) : (
                  <>
                    <AlertCircle className="w-4 h-4 text-red-500" />
                    <span className="text-red-600 font-medium text-sm">Di Luar Area</span>
                  </>
                )}
              </div>
            </div>
            
            {currentLocation && (
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-600">Jarak:</span>
                <span className="text-sm font-medium">
                  {distance < 1000 ? `${Math.round(distance)} m` : `${(distance/1000).toFixed(1)} km`}
                </span>
              </div>
            )}
            
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Radius Diizinkan:</span>
              <span className="text-sm font-medium">{workLocation.radius} m</span>
            </div>
          </div>
        ) : (
          <div className="flex items-center space-x-2 text-amber-600">
            <AlertCircle className="w-4 h-4" />
            <span className="text-sm">Lokasi kerja belum ditetapkan</span>
          </div>
        )}
      </div>

      {/* Map Placeholder */}
      <div className="relative">
        <div ref={mapRef} className="h-64 bg-gray-100 flex items-center justify-center">
          <div className="text-center space-y-2">
            <MapPin className="w-12 h-12 text-gray-400 mx-auto" />
            <p className="text-gray-500 text-sm">Peta Lokasi Kerja</p>
            {workLocation && (
              <p className="text-xs text-gray-400">
                {workLocation.latitude.toFixed(6)}, {workLocation.longitude.toFixed(6)}
              </p>
            )}
          </div>
        </div>

        {/* Location overlay circles */}
        {workLocation && (
          <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            {/* Work location circle */}
            <div 
              className="absolute rounded-full border-2 border-blue-500 bg-blue-100 bg-opacity-30"
              style={{
                width: '100px',
                height: '100px',
              }}
            />
            
            {/* Current location indicator */}
            {currentLocation && (
              <div className={`absolute w-4 h-4 rounded-full ${isWithinGeofence ? 'bg-green-500' : 'bg-red-500'} shadow-lg`}>
                <div className={`absolute inset-0 rounded-full animate-ping ${isWithinGeofence ? 'bg-green-400' : 'bg-red-400'}`} />
              </div>
            )}
          </div>
        )}
      </div>

      {/* GPS Controls */}
      <div className="p-4 space-y-3">
        {!isWatchingLocation ? (
          <button
            onClick={startLocationTracking}
            className="w-full flex items-center justify-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-colors"
          >
            <Navigation className="w-4 h-4" />
            <span>Mulai Tracking GPS</span>
          </button>
        ) : (
          <button
            onClick={stopLocationTracking}
            className="w-full flex items-center justify-center space-x-2 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-colors"
          >
            <Clock className="w-4 h-4" />
            <span>Hentikan Tracking</span>
          </button>
        )}

        {/* Check-in Button */}
        {showCheckInButton && workLocation && currentLocation && (
          <button
            onClick={handleCheckIn}
            disabled={!isWithinGeofence || isCheckingIn}
            className={`w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-lg font-medium transition-colors ${
              isWithinGeofence && !isCheckingIn
                ? 'bg-green-500 hover:bg-green-600 text-white'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            }`}
          >
            {isCheckingIn ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white" />
                <span>Sedang Check-in...</span>
              </>
            ) : (
              <>
                <CheckCircle className="w-4 h-4" />
                <span>{isWithinGeofence ? 'Check-in Sekarang' : 'Di Luar Area Kerja'}</span>
              </>
            )}
          </button>
        )}

        {/* Error Message */}
        {locationError && (
          <div className="flex items-center space-x-2 text-red-600 bg-red-50 p-3 rounded-lg">
            <AlertCircle className="w-4 h-4 flex-shrink-0" />
            <span className="text-sm">{locationError}</span>
          </div>
        )}

        {/* Current Location Info */}
        {currentLocation && (
          <div className="text-xs text-gray-500 space-y-1">
            <div>GPS: {currentLocation.latitude.toFixed(6)}, {currentLocation.longitude.toFixed(6)}</div>
            {currentLocation.accuracy && (
              <div>Akurasi: ±{Math.round(currentLocation.accuracy)} meter</div>
            )}
            <div>Update: {new Date(currentLocation.timestamp).toLocaleTimeString('id-ID')}</div>
          </div>
        )}
      </div>
    </div>
  );
};

export default GeofencingMap;