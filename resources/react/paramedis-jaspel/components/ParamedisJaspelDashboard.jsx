import React, { useState, useEffect } from 'react';
import { Clock, DollarSign, Home, History, Grid, FileText, User, BarChart3 } from 'lucide-react';
import '../styles/ParamedisJaspelDashboard.css';

const ParamedisJaspelDashboard = () => {
  const [dashboardData, setDashboardData] = useState({
    jaspel_monthly: 20400000,
    jaspel_weekly: 5530000,
    minutes_worked: 920,
    doctor_name: 'dr. SARI',
    doctor_specialty: 'Dokter Umum',
    avatar_url: 'https://ui-avatars.com/api/?name=dr.SARI&background=3b82f6&color=fff&size=80&rounded=true'
  });

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Initialize with demo data
    setLoading(false);
    
    // Get user data from Laravel if available
    if (window.laravelData?.user?.name) {
      setDashboardData(prev => ({
        ...prev,
        doctor_name: `dr. ${window.laravelData.user.name.split(' ')[0]}`,
        avatar_url: `https://ui-avatars.com/api/?name=${encodeURIComponent(window.laravelData.user.name)}&background=3b82f6&color=fff&size=80&rounded=true`
      }));
    }
  }, []);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  if (loading) {
    return (
      <div className="jaspel-dashboard-loading">
        <div className="loading-spinner"></div>
        <p>Loading dashboard...</p>
      </div>
    );
  }

  return (
    <div className="jaspel-dashboard">
      {/* iPhone Frame */}
      <div className="iphone-frame">
        
        {/* Main Content Area */}
        <div className="dashboard-content">
          
          {/* Purple-Blue Gradient Header */}
          <div className="gradient-header">
            <div className="header-content">
              <div className="header-text">
                <h1 className="beranda-title">Beranda</h1>
                <h2 className="doctor-name">{dashboardData.doctor_name}</h2>
                <p className="doctor-specialty">- {dashboardData.doctor_specialty}</p>
              </div>
              <div className="profile-avatar">
                <img 
                  src={dashboardData.avatar_url} 
                  alt={dashboardData.doctor_name}
                  className="avatar-image"
                />
              </div>
            </div>
          </div>

          {/* Main Content */}
          <div className="main-dashboard-content">
            
            {/* Large Jaspel Card - Glassmorphic */}
            <div className="jaspel-main-card">
              <p className="jaspel-label">Jaspel Bulan Ini</p>
              <p className="jaspel-amount">{formatCurrency(dashboardData.jaspel_monthly)}</p>
            </div>

            {/* Two Cards Row */}
            <div className="cards-row">
              
              {/* Menit Jaga Card - Yellow/Orange */}
              <div className="stat-card minutes-card">
                <div className="card-icon">
                  <Clock size={24} className="icon" />
                </div>
                <div className="card-content">
                  <p className="card-title">
                    Menit Jaga<br />
                    Bulan Ini
                  </p>
                  <p className="card-value">{dashboardData.minutes_worked} Menit</p>
                </div>
              </div>

              {/* Jaspel Minggu Card - Blue */}
              <div className="stat-card weekly-card">
                <div className="card-icon">
                  <DollarSign size={24} className="icon" />
                </div>
                <div className="card-content">
                  <p className="card-title">
                    Jaspel<br />
                    Minggu Ini
                  </p>
                  <p className="card-value">{formatCurrency(dashboardData.jaspel_weekly)}</p>
                </div>
              </div>

            </div>
          </div>

        </div>

        {/* Bottom Navigation - Exact Match */}
        <div className="bottom-navigation">
          <div className="nav-container">
            
            <div className="nav-item">
              <Home size={20} className="nav-icon" />
              <span className="nav-label">Beranda</span>
            </div>

            <div className="nav-item">
              <History size={20} className="nav-icon" />
              <span className="nav-label">Riwayat</span>
            </div>

            <div className="nav-item active">
              <div className="nav-highlight">
                <Grid size={20} className="nav-icon-active" />
              </div>
              <span className="nav-label-active">Jaspel</span>
            </div>

            <div className="nav-item">
              <FileText size={20} className="nav-icon" />
              <span className="nav-label">Presensi</span>
            </div>

            <div className="nav-item">
              <User size={20} className="nav-icon" />
              <span className="nav-label">Akun</span>
            </div>

          </div>
        </div>

        {/* iPhone Bottom Bar */}
        <div className="iphone-bottom-bar"></div>

      </div>
    </div>
  );
};

export default ParamedisJaspelDashboard;