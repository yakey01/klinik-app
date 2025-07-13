import React, { useState, useEffect, useRef } from 'react';
import { 
  Home, 
  Clock, 
  DollarSign, 
  Calendar, 
  User, 
  TrendingUp, 
  Activity, 
  Star,
  MapPin,
  Heart,
  Plus,
  ChevronRight,
  Bell,
  Settings,
  Award,
  Target,
  Zap
} from 'lucide-react';
import '../styles/PremiumParamedisDashboard.css';

const PremiumParamedisDashboard = () => {
  const [isLoading, setIsLoading] = useState(true);
  const [dashboardData, setDashboardData] = useState({
    jaspel_monthly: 20400000,
    jaspel_weekly: 5530000,
    monthly_hours: 180,
    monthly_target: 25000000,
    completion_rate: 82,
    stress_level: 2,
    energy_level: 4,
    satisfaction: 4,
    doctor_name: 'dr. SARI',
    avatar_url: '/api/placeholder/80/80'
  });
  const [activeCard, setActiveCard] = useState(null);
  const [scrollY, setScrollY] = useState(0);
  const [isOnline, setIsOnline] = useState(true);
  const [notifications, setNotifications] = useState(3);
  const containerRef = useRef(null);

  // Simulated loading with realistic delay
  useEffect(() => {
    const timer = setTimeout(() => {
      setIsLoading(false);
    }, 2000);
    return () => clearTimeout(timer);
  }, []);

  // Get user data from Laravel if available
  useEffect(() => {
    if (window.laravelData?.user?.name && !isLoading) {
      setDashboardData(prev => ({
        ...prev,
        doctor_name: `dr. ${window.laravelData.user.name.split(' ')[0].toUpperCase()}`,
        ...window.laravelData.dashboardData
      }));
    }
  }, [isLoading]);

  // Scroll handler for parallax effects
  useEffect(() => {
    const handleScroll = () => {
      if (containerRef.current) {
        setScrollY(containerRef.current.scrollTop);
      }
    };

    const container = containerRef.current;
    if (container) {
      container.addEventListener('scroll', handleScroll);
      return () => container.removeEventListener('scroll', handleScroll);
    }
  }, []);

  // Format currency
  const formatCurrency = (amount) => {
    if (amount >= 1000000) {
      return `Rp ${(amount / 1000000).toFixed(1)}M`;
    }
    return `Rp ${(amount / 1000).toFixed(0)}K`;
  };

  // Haptic feedback simulation
  const hapticFeedback = (type = 'light') => {
    if (navigator.vibrate) {
      const patterns = {
        light: [10],
        medium: [20],
        heavy: [30],
        success: [10, 10, 10],
        error: [50, 20, 50]
      };
      navigator.vibrate(patterns[type] || patterns.light);
    }
  };

  // Card press handler with animation
  const handleCardPress = (cardId) => {
    hapticFeedback('light');
    setActiveCard(cardId);
    setTimeout(() => setActiveCard(null), 150);
  };

  // Quick action handlers
  const handleQuickAction = (action) => {
    hapticFeedback('medium');
    console.log(`Quick action: ${action}`);
    // Add your navigation logic here
  };

  if (isLoading) {
    return <LoadingScreen />;
  }

  return (
    <div className="premium-dashboard">
      <div className="dashboard-container" ref={containerRef}>
        
        {/* Animated Background */}
        <div className="animated-background">
          <div className="floating-shapes">
            <div className="shape shape-1"></div>
            <div className="shape shape-2"></div>
            <div className="shape shape-3"></div>
            <div className="shape shape-4"></div>
          </div>
        </div>

        {/* Status Bar */}
        <div className="status-bar">
          <div className="status-left">
            <div className={`online-indicator ${isOnline ? 'online' : 'offline'}`}></div>
            <span className="status-text">
              {isOnline ? 'Online' : 'Offline'}
            </span>
          </div>
          <div className="status-right">
            <div className="notification-badge">
              <Bell size={16} />
              {notifications > 0 && (
                <span className="badge-count">{notifications}</span>
              )}
            </div>
            <div className="settings-btn" onClick={() => hapticFeedback('light')}>
              <Settings size={16} />
            </div>
          </div>
        </div>

        {/* Header Section with Parallax */}
        <div 
          className="dashboard-header"
          style={{
            transform: `translateY(${scrollY * 0.3}px)`,
          }}
        >
          <div className="header-content">
            <div className="welcome-section">
              <div className="avatar-container">
                <div className="avatar-ring"></div>
                <img 
                  src={dashboardData.avatar_url} 
                  alt="Avatar"
                  className="user-avatar"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'flex';
                  }}
                />
                <div className="avatar-fallback">
                  {dashboardData.doctor_name.slice(-4, -1)}
                </div>
                <div className="avatar-status"></div>
              </div>
              <div className="welcome-text">
                <h1 className="greeting">Good Morning!</h1>
                <h2 className="doctor-name">{dashboardData.doctor_name}</h2>
                <p className="role-badge">
                  <Star size={12} />
                  Premium Paramedis
                </p>
              </div>
            </div>
            
            {/* Quick Stats with Animations */}
            <div className="quick-stats">
              <div className="stat-card earnings">
                <div className="stat-icon">
                  <TrendingUp size={20} />
                </div>
                <div className="stat-content">
                  <span className="stat-value">{formatCurrency(dashboardData.jaspel_monthly)}</span>
                  <span className="stat-label">This Month</span>
                </div>
                <div className="stat-trend positive">
                  +12%
                </div>
              </div>
              
              <div className="stat-card hours">
                <div className="stat-icon">
                  <Clock size={20} />
                </div>
                <div className="stat-content">
                  <span className="stat-value">{dashboardData.monthly_hours}h</span>
                  <span className="stat-label">Total Hours</span>
                </div>
                <div className="completion-ring">
                  <svg className="ring-svg" viewBox="0 0 36 36">
                    <path
                      className="ring-bg"
                      d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      className="ring-progress"
                      strokeDasharray={`${dashboardData.completion_rate}, 100`}
                      d="M18 2.0845
                        a 15.9155 15.9155 0 0 1 0 31.831
                        a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <span className="ring-text">{dashboardData.completion_rate}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="main-content">
          
          {/* Feature Cards Grid */}
          <div className="feature-cards">
            
            {/* Main Jaspel Card */}
            <div 
              className={`feature-card main-card ${activeCard === 'jaspel' ? 'pressed' : ''}`}
              onClick={() => handleCardPress('jaspel')}
            >
              <div className="card-background">
                <div className="gradient-overlay"></div>
                <div className="pattern-overlay"></div>
              </div>
              
              <div className="card-content">
                <div className="card-header">
                  <div className="card-icon">
                    <DollarSign size={24} />
                  </div>
                  <div className="card-menu">
                    <ChevronRight size={16} />
                  </div>
                </div>
                
                <div className="card-body">
                  <h3 className="card-title">Jaspel Earnings</h3>
                  <div className="amount-section">
                    <span className="main-amount">{formatCurrency(dashboardData.jaspel_monthly)}</span>
                    <span className="amount-period">This Month</span>
                  </div>
                  
                  <div className="progress-section">
                    <div className="progress-info">
                      <span>Target: {formatCurrency(dashboardData.monthly_target)}</span>
                      <span>{dashboardData.completion_rate}%</span>
                    </div>
                    <div className="progress-bar">
                      <div 
                        className="progress-fill"
                        style={{ width: `${dashboardData.completion_rate}%` }}
                      ></div>
                    </div>
                  </div>
                </div>
                
                <div className="card-footer">
                  <div className="mini-chart">
                    <div className="chart-bars">
                      {[40, 60, 30, 70, 50, 80, 60].map((height, index) => (
                        <div 
                          key={index}
                          className="chart-bar"
                          style={{ 
                            height: `${height}%`,
                            animationDelay: `${index * 0.1}s`
                          }}
                        ></div>
                      ))}
                    </div>
                  </div>
                  <span className="trend-indicator">
                    <TrendingUp size={12} />
                    +12% from last month
                  </span>
                </div>
              </div>
            </div>

            {/* Action Cards */}
            <div className="action-cards">
              
              {/* Attendance Card */}
              <div 
                className={`action-card attendance ${activeCard === 'attendance' ? 'pressed' : ''}`}
                onClick={() => handleCardPress('attendance')}
              >
                <div className="action-icon">
                  <Clock size={20} />
                </div>
                <div className="action-content">
                  <h4>Attendance</h4>
                  <p>Check In/Out</p>
                </div>
                <div className="action-indicator">
                  <div className="pulse-dot"></div>
                </div>
              </div>

              {/* Schedule Card */}
              <div 
                className={`action-card schedule ${activeCard === 'schedule' ? 'pressed' : ''}`}
                onClick={() => handleCardPress('schedule')}
              >
                <div className="action-icon">
                  <Calendar size={20} />
                </div>
                <div className="action-content">
                  <h4>Schedule</h4>
                  <p>View shifts</p>
                </div>
                <div className="schedule-preview">
                  <span>Today: 07:00-15:00</span>
                </div>
              </div>

              {/* Performance Card */}
              <div 
                className={`action-card performance ${activeCard === 'performance' ? 'pressed' : ''}`}
                onClick={() => handleCardPress('performance')}
              >
                <div className="action-icon">
                  <Activity size={20} />
                </div>
                <div className="action-content">
                  <h4>Performance</h4>
                  <p>View metrics</p>
                </div>
                <div className="performance-score">
                  <span>{dashboardData.satisfaction}/5</span>
                  <Star size={12} />
                </div>
              </div>

              {/* Profile Card */}
              <div 
                className={`action-card profile ${activeCard === 'profile' ? 'pressed' : ''}`}
                onClick={() => handleCardPress('profile')}
              >
                <div className="action-icon">
                  <User size={20} />
                </div>
                <div className="action-content">
                  <h4>Profile</h4>
                  <p>Settings</p>
                </div>
                <div className="profile-status">
                  <div className="status-dot verified"></div>
                </div>
              </div>
            </div>
          </div>

          {/* Recent Activities */}
          <div className="section recent-activities">
            <div className="section-header">
              <h3>Recent Activities</h3>
              <button className="see-all-btn">
                See All
                <ChevronRight size={14} />
              </button>
            </div>
            
            <div className="activities-list">
              {[
                {
                  id: 1,
                  type: 'earning',
                  title: 'Tindakan Medis Completed',
                  amount: '+Rp 450K',
                  time: '2 hours ago',
                  icon: Award,
                  color: 'green'
                },
                {
                  id: 2,
                  type: 'attendance',
                  title: 'Check-in Successful',
                  time: 'Today at 07:00',
                  icon: Clock,
                  color: 'blue'
                },
                {
                  id: 3,
                  type: 'achievement',
                  title: 'Monthly Target Achieved',
                  time: 'Yesterday',
                  icon: Target,
                  color: 'purple'
                }
              ].map((activity) => (
                <div key={activity.id} className="activity-item">
                  <div className={`activity-icon ${activity.color}`}>
                    <activity.icon size={16} />
                  </div>
                  <div className="activity-content">
                    <h4>{activity.title}</h4>
                    <p>{activity.time}</p>
                  </div>
                  {activity.amount && (
                    <div className="activity-amount">{activity.amount}</div>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Wellness Section */}
          <div className="section wellness">
            <div className="section-header">
              <h3>
                <Heart size={16} />
                Daily Wellness
              </h3>
            </div>
            
            <div className="wellness-cards">
              <div className="wellness-card">
                <div className="wellness-icon">
                  <Zap size={16} />
                </div>
                <div className="wellness-content">
                  <span className="wellness-label">Energy Level</span>
                  <div className="wellness-meter">
                    <div 
                      className="meter-fill energy"
                      style={{ width: `${(dashboardData.energy_level / 5) * 100}%` }}
                    ></div>
                  </div>
                  <span className="wellness-value">{dashboardData.energy_level}/5</span>
                </div>
              </div>
              
              <div className="wellness-card">
                <div className="wellness-icon">
                  <Activity size={16} />
                </div>
                <div className="wellness-content">
                  <span className="wellness-label">Stress Level</span>
                  <div className="wellness-meter">
                    <div 
                      className="meter-fill stress"
                      style={{ width: `${(dashboardData.stress_level / 5) * 100}%` }}
                    ></div>
                  </div>
                  <span className="wellness-value">{dashboardData.stress_level}/5</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Floating Action Button */}
        <div className="floating-action">
          <button 
            className="fab"
            onClick={() => handleQuickAction('quick-checkin')}
          >
            <Plus size={24} />
            <div className="fab-ripple"></div>
          </button>
          <span className="fab-label">Quick Check-in</span>
        </div>

        {/* Bottom Navigation */}
        <div className="bottom-navigation">
          <div className="nav-container">
            {[
              { icon: Home, label: 'Home', active: true },
              { icon: Activity, label: 'Activity' },
              { icon: Calendar, label: 'Schedule' },
              { icon: MapPin, label: 'Location' },
              { icon: User, label: 'Profile' }
            ].map((item, index) => (
              <button 
                key={index}
                className={`nav-item ${item.active ? 'active' : ''}`}
                onClick={() => hapticFeedback('light')}
              >
                <item.icon size={20} />
                <span>{item.label}</span>
                {item.active && <div className="nav-indicator"></div>}
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

// Premium Loading Screen Component
const LoadingScreen = () => {
  return (
    <div className="loading-screen">
      <div className="loading-content">
        <div className="loading-logo">
          <div className="logo-ring">
            <div className="logo-inner">
              <Activity size={32} />
            </div>
          </div>
        </div>
        
        <div className="loading-text">
          <h2>Dokterku</h2>
          <p>Loading your dashboard...</p>
        </div>
        
        <div className="loading-progress">
          <div className="progress-track">
            <div className="progress-bar loading-animation"></div>
          </div>
        </div>
        
        <div className="loading-dots">
          <div className="dot"></div>
          <div className="dot"></div>
          <div className="dot"></div>
        </div>
      </div>
      
      <div className="loading-background">
        <div className="loading-particle"></div>
        <div className="loading-particle"></div>
        <div className="loading-particle"></div>
      </div>
    </div>
  );
};

export default PremiumParamedisDashboard;