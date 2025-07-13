import React, { useState, useEffect } from 'react';
import { Clock, DollarSign, Home, History, Grid, FileText, User, MapPin, Calendar } from 'lucide-react';
import '../styles/ParamedisDashboard.css';

const ParamedisDashboard = () => {
  const [dashboardData, setDashboardData] = useState({
    jaspel_monthly: 15200000,
    jaspel_weekly: 3800000,
    minutes_worked: 720,
    shifts_this_month: 22,
    paramedis_name: 'Dr. Sari',
    paramedis_specialty: 'Dokter Spesialis',
    pending_jaspel: 2400000,
    approved_jaspel: 12800000,
    today_attendance: null
  });

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // For demo: just set loading to false and use default data
    setLoading(false);
    
    // Try to get user name from Laravel data if available
    if (window.laravelData?.user?.name) {
      setDashboardData(prev => ({
        ...prev,
        paramedis_name: window.laravelData.user.name
      }));
    }
    
    // Optionally try to fetch API data
    // fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const response = await fetch('/api/paramedis/dashboard', {
        method: 'GET',
        credentials: 'include', // Include cookies for session-based auth
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': window.laravelData?.csrfToken || '',
          'Accept': 'application/json'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        setDashboardData(data);
      }
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
  };

  if (loading) {
    return (
      <div className="paramedis-dashboard-loading">
        <div className="loading-spinner"></div>
        <p>Loading dashboard...</p>
      </div>
    );
  }

  return (
    <div className="paramedis-dashboard">
      {/* Phone Frame */}
      <div className="phone-frame">
        <div className="phone-header">
          {/* Status Bar */}
          <div className="status-bar">
            <div className="status-left">
              <span className="time">{new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
            </div>
            <div className="status-right">
              <div className="signal-bars"></div>
              <div className="battery"></div>
            </div>
          </div>
          
          {/* Header */}
          <div className="header-content">
            <div className="header-info">
              <div>
                <h1 className="greeting">{getGreeting()}</h1>
                <h2 className="paramedis-name">{dashboardData.paramedis_name}</h2>
                <p className="paramedis-specialty">- {dashboardData.paramedis_specialty}</p>
              </div>
              <div className="profile-picture">
                <div className="profile-inner">
                  <User size={20} className="profile-icon" />
                </div>
                <div className="online-status"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="main-content">
        {/* Main Jaspel Card */}
        <div className="jaspel-main-card">
          <div className="jaspel-header">
            <p className="jaspel-label">💰 Total Jaspel Bulan Ini</p>
          </div>
          <p className="jaspel-amount">{formatCurrency(dashboardData.jaspel_monthly)}</p>
          <div className="jaspel-breakdown">
            <div className="breakdown-item approved">
              <span className="status-indicator">✅</span>
              <span className="breakdown-label">Disetujui</span>
              <span className="breakdown-amount">{formatCurrency(dashboardData.approved_jaspel)}</span>
            </div>
            <div className="breakdown-item pending">
              <span className="status-indicator">⏳</span>
              <span className="breakdown-label">Pending</span>
              <span className="breakdown-amount">{formatCurrency(dashboardData.pending_jaspel)}</span>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="stats-container">
          {/* Weekly Jaspel Card */}
          <div className="stat-card weekly-card">
            <div className="stat-icon-container weekly-icon">
              <DollarSign size={18} className="stat-icon" />
            </div>
            <div className="stat-content">
              <p className="stat-title">
                Jaspel<br />Minggu Ini
              </p>
              <p className="stat-value">{formatCurrency(dashboardData.jaspel_weekly)}</p>
            </div>
          </div>

          {/* Minutes Card */}
          <div className="stat-card minutes-card">
            <div className="stat-icon-container minutes-icon">
              <Clock size={18} className="stat-icon" />
            </div>
            <div className="stat-content">
              <p className="stat-title">
                Jam Kerja<br />Bulan Ini
              </p>
              <p className="stat-value">{Math.round(dashboardData.minutes_worked / 60)} Jam</p>
            </div>
          </div>

          {/* Shifts Card */}
          <div className="stat-card shifts-card">
            <div className="stat-icon-container shifts-icon">
              <Calendar size={18} className="stat-icon" />
            </div>
            <div className="stat-content">
              <p className="stat-title">
                Shift<br />Bulan Ini
              </p>
              <p className="stat-value">{dashboardData.shifts_this_month} Hari</p>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="quick-actions">
          <button 
            className="action-btn primary-action"
            onClick={() => {
              window.location.href = '/paramedis/pages/presensi-mobile';
            }}
          >
            <div className="action-icon">
              <MapPin size={20} />
            </div>
            <div className="action-content">
              <span className="action-title">Check In/Out</span>
              <span className="action-subtitle">Presensi Harian</span>
            </div>
          </button>

          <button 
            className="action-btn secondary-action"
            onClick={() => {
              window.location.href = '/paramedis/resources/jaspels';
            }}
          >
            <div className="action-icon">
              <History size={20} />
            </div>
            <div className="action-content">
              <span className="action-title">Lihat Riwayat</span>
              <span className="action-subtitle">Data Jaspel</span>
            </div>
          </button>
        </div>
      </div>

      {/* Bottom Navigation */}
      <div className="bottom-nav">
        <div className="nav-container">
          <div 
            className="nav-item active"
            onClick={() => window.location.href = '/paramedis'}
          >
            <Home size={22} className="nav-icon active-icon" />
            <span className="nav-text active-text">Beranda</span>
          </div>
          
          <div 
            className="nav-item"
            onClick={() => window.location.href = '/paramedis/resources/attendances'}
          >
            <History size={22} className="nav-icon" />
            <span className="nav-text">Riwayat</span>
          </div>
          
          <div 
            className="nav-item"
            onClick={() => window.location.href = '/paramedis/resources/jaspels'}
          >
            <div className="nav-highlight">
              <Grid size={22} className="nav-icon-white" />
            </div>
            <span className="nav-text active-text">Jaspel</span>
          </div>
          
          <div 
            className="nav-item"
            onClick={() => window.location.href = '/paramedis/pages/presensi-mobile'}
          >
            <FileText size={22} className="nav-icon" />
            <span className="nav-text">Presensi</span>
          </div>
          
          <div 
            className="nav-item"
            onClick={() => window.location.href = '/paramedis'}
          >
            <User size={22} className="nav-icon" />
            <span className="nav-text">Akun</span>
          </div>
        </div>
      </div>

      {/* Phone Bottom Bar */}
      <div className="phone-bottom"></div>
    </div>
  );
};

export default ParamedisDashboard;