import { useState, useCallback } from 'react';

interface UseApiWithAuthOptions {
  showErrorNotification?: boolean;
}

export const useApiWithAuth = (options: UseApiWithAuthOptions = {}) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchWithAuth = useCallback(async (url: string, requestOptions?: RequestInit) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(url, {
        ...requestOptions,
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...requestOptions?.headers,
        },
      });

      if (response.status === 404) {
        // For 404 errors, try alternative endpoints
        const alternativeEndpoints: Record<string, string> = {
          '/test-paramedis-dashboard-api': '/api/new-paramedis/dashboard',
          '/test-paramedis-attendance-summary': '/api/paramedis/attendance/status',
        };

        const altUrl = alternativeEndpoints[url];
        if (altUrl) {
          console.log(`Trying alternative endpoint: ${altUrl}`);
          const altResponse = await fetch(altUrl, {
            ...requestOptions,
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              ...requestOptions?.headers,
            },
          });

          if (altResponse.ok) {
            const data = await altResponse.json();
            setLoading(false);
            return { data, response: altResponse };
          }
        }

        throw new Error(`Endpoint not found: ${url}`);
      }

      if (response.status === 401) {
        // Redirect to login if unauthorized
        window.location.href = '/login';
        throw new Error('Unauthorized');
      }

      if (!response.ok) {
        throw new Error(`Request failed: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      setLoading(false);
      return { data, response };
    } catch (err) {
      setError(err instanceof Error ? err.message : 'An error occurred');
      setLoading(false);
      
      if (options.showErrorNotification) {
        console.error(`API Error: ${err instanceof Error ? err.message : 'Unknown error'}`);
      }
      
      throw err;
    }
  }, [options.showErrorNotification]);

  return {
    fetchWithAuth,
    loading,
    error,
  };
};