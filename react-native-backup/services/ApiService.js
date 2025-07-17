/**
 * API Service for connecting React Native app with Laravel backend
 * Handles authentication, data fetching, and error handling
 */

const BASE_URL = 'http://127.0.0.1:8000'; // Laravel development server
// For production: const BASE_URL = 'https://your-domain.com';

class ApiService {
  constructor() {
    this.token = null;
    this.baseURL = BASE_URL;
  }

  /**
   * Set authentication token
   */
  setAuthToken(token) {
    this.token = token;
  }

  /**
   * Get default headers for API requests
   */
  getHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
    };
  }

  /**
   * Make authenticated API request
   */
  async makeRequest(endpoint, options = {}) {
    try {
      const url = `${this.baseURL}/api${endpoint}`;
      const config = {
        headers: this.getHeaders(),
        ...options,
      };

      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API Request failed:', error);
      throw error;
    }
  }

  /**
   * Authentication
   */
  async login(email, password) {
    try {
      const response = await this.makeRequest('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });

      if (response.token) {
        this.setAuthToken(response.token);
      }

      return response;
    } catch (error) {
      throw new Error('Login failed: ' + error.message);
    }
  }

  /**
   * Get user profile
   */
  async getUserProfile() {
    return this.makeRequest('/user');
  }

  /**
   * Jaspel-related endpoints
   */
  async getJaspelSummary() {
    return this.makeRequest('/paramedis/jaspel/summary');
  }

  async getJaspelMonthly() {
    return this.makeRequest('/paramedis/jaspel/monthly');
  }

  async getJaspelWeekly() {
    return this.makeRequest('/paramedis/jaspel/weekly');
  }

  async getJaspelHistory(limit = 10) {
    return this.makeRequest(`/paramedis/jaspel/history?limit=${limit}`);
  }

  async getJaspelDetail(id) {
    return this.makeRequest(`/paramedis/jaspel/${id}`);
  }

  /**
   * Attendance endpoints
   */
  async getAttendanceStatus() {
    return this.makeRequest('/paramedis/attendance/status');
  }

  async checkin(locationData) {
    return this.makeRequest('/paramedis/attendance/checkin', {
      method: 'POST',
      body: JSON.stringify(locationData),
    });
  }

  async checkout(locationData) {
    return this.makeRequest('/paramedis/attendance/checkout', {
      method: 'POST',
      body: JSON.stringify(locationData),
    });
  }

  /**
   * Dashboard data
   */
  async getDashboardData() {
    return this.makeRequest('/paramedis/dashboard');
  }
}

// Export singleton instance
export default new ApiService();