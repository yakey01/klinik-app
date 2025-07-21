import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui/button';
import { MapPin, Navigation, Loader2 } from 'lucide-react';

interface SimpleGoogleMapProps {
  onLocationSelect?: (location: { lat: number; lng: number; accuracy?: number; address?: string }) => void;
  height?: string;
}

export function SimpleGoogleMap({ onLocationSelect, height = '300px' }: SimpleGoogleMapProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isGpsLoading, setIsGpsLoading] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;

    const loadMap = async () => {
      try {
        if (!mounted || !mapRef.current) {
          return;
        }

        // Simple fallback without complex libraries
        if (!window.google) {
          if (mounted) {
            setError('Google Maps tidak tersedia. Menggunakan GPS saja.');
            setIsLoading(false);
          }
          return;
        }

        const map = new google.maps.Map(mapRef.current, {
          center: { lat: -6.200000, lng: 106.816666 },
          zoom: 15,
          mapTypeControl: true,
          streetViewControl: true,
        });

        const marker = new google.maps.Marker({
          position: { lat: -6.200000, lng: 106.816666 },
          map: map,
          draggable: true,
          title: 'Lokasi Presensi'
        });

        // Wrap event listeners with try-catch
        try {
          map.addListener('click', (e: any) => {
            try {
              if (!mounted || !e.latLng) return;
              
              const lat = parseFloat(e.latLng.lat().toFixed(8));
              const lng = parseFloat(e.latLng.lng().toFixed(8));
              
              if (marker && map) {
                marker.setPosition({ lat, lng });
                setCurrentLocation({ lat, lng });
                
                if (onLocationSelect) {
                  onLocationSelect({ lat, lng });
                }
              }
            } catch (clickError) {
              console.warn('Map click error:', clickError);
            }
          });

          marker.addListener('dragend', () => {
            try {
              if (!mounted) return;
              
              const position = marker.getPosition();
              if (position) {
                const lat = parseFloat(position.lat().toFixed(8));
                const lng = parseFloat(position.lng().toFixed(8));
                
                setCurrentLocation({ lat, lng });
                
                if (onLocationSelect) {
                  onLocationSelect({ lat, lng });
                }
              }
            } catch (dragError) {
              console.warn('Marker drag error:', dragError);
            }
          });
        } catch (listenerError) {
          console.warn('Event listener setup error:', listenerError);
        }

        if (mounted) {
          setIsLoading(false);
        }

      } catch (err) {
        console.error('Map loading error:', err);
        if (mounted) {
          setError('Gagal memuat peta');
          setIsLoading(false);
        }
      }
    };

    // Add global error handler for unhandled promises
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      if (event.reason?.message?.includes('IntersectionObserver') || 
          event.reason?.message?.includes('target') ||
          event.reason?.message?.includes('Element')) {
        console.warn('Suppressed Maps-related error:', event.reason);
        event.preventDefault();
      }
    };

    window.addEventListener('unhandledrejection', handleUnhandledRejection);

    const timer = setTimeout(() => {
      loadMap().catch(err => {
        console.warn('Async map loading error:', err);
        if (mounted) {
          setError('Gagal memuat peta');
          setIsLoading(false);
        }
      });
    }, 300);

    return () => {
      mounted = false;
      clearTimeout(timer);
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);
    };
  }, [onLocationSelect]);

  const handleGpsClick = () => {
    if (!navigator.geolocation) {
      alert('GPS tidak didukung oleh browser Anda');
      return;
    }

    setIsGpsLoading(true);

    // Wrap geolocation in try-catch and promise
    const getLocation = new Promise<GeolocationPosition>((resolve, reject) => {
      try {
        navigator.geolocation.getCurrentPosition(
          (position) => resolve(position),
          (error) => reject(error),
          {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 60000
          }
        );
      } catch (err) {
        reject(err);
      }
    });

    getLocation
      .then((position) => {
        try {
          const lat = parseFloat(position.coords.latitude.toFixed(8));
          const lng = parseFloat(position.coords.longitude.toFixed(8));
          const accuracy = Math.round(position.coords.accuracy);

          setCurrentLocation({ lat, lng });
          setIsGpsLoading(false);

          if (onLocationSelect) {
            onLocationSelect({ lat, lng, accuracy });
          }

          alert(`Lokasi terdeteksi!\nLat: ${lat}\nLng: ${lng}\nAkurasi: ${accuracy}m`);
        } catch (err) {
          console.warn('GPS processing error:', err);
          setIsGpsLoading(false);
          alert('Error memproses data GPS');
        }
      })
      .catch((error) => {
        console.warn('GPS error:', error);
        setIsGpsLoading(false);
        
        let message = 'Gagal mendeteksi lokasi GPS';
        if (error.code === 1) message = 'Akses lokasi ditolak';
        if (error.code === 2) message = 'Lokasi tidak tersedia';
        if (error.code === 3) message = 'Timeout GPS';
        
        alert(message);
      });
  };

  if (error) {
    return (
      <div className="space-y-3">
        <Button 
          onClick={handleGpsClick}
          disabled={isGpsLoading}
          className="w-full bg-blue-600 hover:bg-blue-700 text-white"
          size="sm"
        >
          {isGpsLoading ? (
            <>
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              Mendeteksi GPS...
            </>
          ) : (
            <>
              <Navigation className="w-4 h-4 mr-2" />
              Gunakan GPS Saja
            </>
          )}
        </Button>
        
        {currentLocation && (
          <div className="p-3 bg-green-50 dark:bg-green-950/30 rounded-lg">
            <div className="text-sm text-green-700 dark:text-green-300">
              <p><strong>Lokasi:</strong> {currentLocation.lat.toFixed(6)}, {currentLocation.lng.toFixed(6)}</p>
            </div>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <Button 
        onClick={handleGpsClick}
        disabled={isGpsLoading}
        className="w-full bg-blue-600 hover:bg-blue-700 text-white"
        size="sm"
      >
        {isGpsLoading ? (
          <>
            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
            Mendeteksi GPS...
          </>
        ) : (
          <>
            <Navigation className="w-4 h-4 mr-2" />
            Deteksi Lokasi GPS
          </>
        )}
      </Button>

      <div className="relative rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700">
        {isLoading && (
          <div 
            className="flex items-center justify-center bg-gray-100 dark:bg-gray-800"
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
          style={{ height, width: '100%' }}
          className={isLoading ? 'hidden' : 'block'}
        />
      </div>

      {currentLocation && (
        <div className="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
          <div className="flex items-center gap-2 text-sm mb-2">
            <MapPin className="w-4 h-4 text-blue-600" />
            <span className="font-medium text-gray-900 dark:text-white">Lokasi Dipilih:</span>
          </div>
          
          <div className="text-sm text-gray-600 dark:text-gray-300">
            <p><strong>Koordinat:</strong> {currentLocation.lat.toFixed(6)}, {currentLocation.lng.toFixed(6)}</p>
          </div>
        </div>
      )}

      <div className="text-xs text-gray-500 dark:text-gray-400 text-center">
        Klik pada peta atau gunakan GPS untuk memilih lokasi
      </div>
    </div>
  );
}

export default SimpleGoogleMap;