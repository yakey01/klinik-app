import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, useCallback } from 'react';

interface DashboardData {
  success: boolean;
  message: string;
  data: {
    user: any;
    paramedis: any;
    stats: any;
    performance: any;
    next_schedule: any;
    current_time: string;
    current_date: string;
    greeting: string;
  };
  meta: {
    version: string;
    timestamp: string;
    request_id: string;
  };
}

interface JaspelSummaryData {
  success: boolean;
  message: string;
  data: {
    summary: {
      current_month: number;
      previous_month: number;
      growth_percent: number;
      daily_average: number;
      weekly_estimate: number;
      approval_rate: number;
    };
    period_info: {
      month: number;
      year: number;
      month_name: string;
      days_in_month: number;
      days_passed: number;
      month_progress: number;
      is_current_month: boolean;
    };
    breakdown: any;
    recent_transactions: any[];
    performance_indicators: any;
  };
  meta: any;
}

interface UpcomingSchedulesData {
  success: boolean;
  message: string;
  data: {
    schedules: any[];
    total_count: number;
    period_info: any;
    summary: any;
  };
  meta: any;
}

// API client with enhanced error handling and authentication
class DashboardAPI {
  private baseURL = '/paramedis/web-api';
  
  private async fetchWithAuth(url: string, options: RequestInit = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      ...options.headers as Record<string, string>,
    };
    
    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }
    
    const response = await fetch(url, {
      credentials: 'include',
      ...options,
      headers,
    });
    
    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`);
    }
    
    return response.json();
  }
  
  async getDashboardData(): Promise<DashboardData> {
    return this.fetchWithAuth(`${this.baseURL}/dashboard-stats`);
  }
  
  async getJaspelSummary(month?: number, year?: number): Promise<JaspelSummaryData> {
    const params = new URLSearchParams();
    if (month) params.append('month', month.toString());
    if (year) params.append('year', year.toString());
    
    const url = `${this.baseURL}/jaspel/summary${params.toString() ? '?' + params.toString() : ''}`;
    return this.fetchWithAuth(url);
  }
  
  async getUpcomingSchedules(limit = 10, daysAhead = 14): Promise<UpcomingSchedulesData> {
    const params = new URLSearchParams({
      limit: limit.toString(),
      days_ahead: daysAhead.toString(),
    });
    
    return this.fetchWithAuth(`${this.baseURL}/schedules/upcoming?${params.toString()}`);
  }
  
  async refreshWorkLocation(): Promise<any> {
    return this.fetchWithAuth(`${this.baseURL}/refresh-work-location`, {
      method: 'POST',
    });
  }
}

const dashboardAPI = new DashboardAPI();

// Query keys for better cache management
export const dashboardKeys = {
  all: ['dashboard'] as const,
  dashboard: () => [...dashboardKeys.all, 'main'] as const,
  jaspel: (month?: number, year?: number) => [...dashboardKeys.all, 'jaspel', { month, year }] as const,
  schedules: (limit?: number, daysAhead?: number) => [...dashboardKeys.all, 'schedules', { limit, daysAhead }] as const,
};

// Main dashboard data hook
export function useDashboardData() {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const queryClient = useQueryClient();
  
  const query = useQuery({
    queryKey: dashboardKeys.dashboard(),
    queryFn: dashboardAPI.getDashboardData,
    staleTime: 2 * 60 * 1000, // 2 minutes
    gcTime: 5 * 60 * 1000, // 5 minutes
    refetchOnWindowFocus: true,
    refetchOnReconnect: true,
    retry: (failureCount, error) => {
      // Don't retry on auth errors
      if (error.message.includes('401') || error.message.includes('403')) {
        return false;
      }
      return failureCount < 3;
    },
  });
  
  const refresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: dashboardKeys.dashboard() }),
        queryClient.invalidateQueries({ queryKey: dashboardKeys.jaspel() }),
        queryClient.invalidateQueries({ queryKey: dashboardKeys.schedules() }),
      ]);
    } finally {
      setTimeout(() => setIsRefreshing(false), 1000);
    }
  }, [queryClient]);
  
  return {
    ...query,
    refresh,
    isRefreshing,
  };
}

// Jaspel summary hook
export function useJaspelSummary(month?: number, year?: number) {
  return useQuery({
    queryKey: dashboardKeys.jaspel(month, year),
    queryFn: () => dashboardAPI.getJaspelSummary(month, year),
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
    enabled: true,
  });
}

// Upcoming schedules hook
export function useUpcomingSchedules(limit = 10, daysAhead = 14) {
  return useQuery({
    queryKey: dashboardKeys.schedules(limit, daysAhead),
    queryFn: () => dashboardAPI.getUpcomingSchedules(limit, daysAhead),
    staleTime: 3 * 60 * 1000, // 3 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
  });
}

// Work location refresh hook
export function useWorkLocationRefresh() {
  const queryClient = useQueryClient();
  
  const refresh = useCallback(async () => {
    try {
      await dashboardAPI.refreshWorkLocation();
      // Invalidate dashboard data to get fresh work location info
      await queryClient.invalidateQueries({ queryKey: dashboardKeys.dashboard() });
      return { success: true };
    } catch (error) {
      console.error('Failed to refresh work location:', error);
      return { success: false, error };
    }
  }, [queryClient]);
  
  return { refresh };
}

// Combined dashboard hook with all data
export function useCompleteDashboard() {
  const dashboardData = useDashboardData();
  const jaspelSummary = useJaspelSummary();
  const upcomingSchedules = useUpcomingSchedules();
  
  const isLoading = dashboardData.isLoading || jaspelSummary.isLoading;
  const isError = dashboardData.isError || jaspelSummary.isError;
  const error = dashboardData.error || jaspelSummary.error;
  
  return {
    dashboard: dashboardData,
    jaspel: jaspelSummary,
    schedules: upcomingSchedules,
    isLoading,
    isError,
    error,
    refresh: dashboardData.refresh,
    isRefreshing: dashboardData.isRefreshing,
  };
}

// Performance monitoring hook
export function useDashboardPerformance() {
  const [metrics, setMetrics] = useState({
    loadTime: 0,
    renderTime: 0,
    apiCalls: 0,
    cacheHits: 0,
  });
  
  const queryClient = useQueryClient();
  
  const trackMetric = useCallback((type: string, value: number) => {
    setMetrics(prev => ({
      ...prev,
      [type]: value,
    }));
  }, []);
  
  const getCacheStats = useCallback(() => {
    const cache = queryClient.getQueryCache();
    const queries = cache.getAll();
    
    return {
      totalQueries: queries.length,
      freshQueries: queries.filter(q => q.state.dataUpdatedAt > Date.now() - (2 * 60 * 1000)).length,
      staleQueries: queries.filter(q => q.isStale()).length,
      invalidQueries: queries.filter(q => q.state.status === 'error').length,
    };
  }, [queryClient]);
  
  return {
    metrics,
    trackMetric,
    getCacheStats,
  };
}