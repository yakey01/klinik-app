import React from 'react';
import { createRoot } from 'react-dom/client';
import PremiumParamedisDashboard from './components/PremiumParamedisDashboard.jsx';

// Enhanced Error Boundary with Premium Styling
class PremiumErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { 
      hasError: false, 
      error: null,
      errorInfo: null
    };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Premium React Error Boundary caught an error:', error, errorInfo);
    this.setState({
      error,
      errorInfo
    });
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'linear-gradient(135deg, #F0F4FF 0%, #E8F2FF 50%, #DDE7FF 100%)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          zIndex: 10000
        }}>
          <div style={{
            background: 'white',
            borderRadius: '24px',
            padding: '40px',
            maxWidth: '400px',
            margin: '20px',
            boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
            textAlign: 'center',
            border: '1px solid rgba(0, 122, 255, 0.1)'
          }}>
            <div style={{
              width: '64px',
              height: '64px',
              borderRadius: '50%',
              background: 'linear-gradient(135deg, #FF453A 0%, #FF6B35 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 24px',
              color: 'white',
              fontSize: '24px'
            }}>
              ⚠️
            </div>
            
            <h3 style={{ 
              color: '#1F2937', 
              marginBottom: '12px',
              fontSize: '20px',
              fontWeight: '700'
            }}>
              Something went wrong
            </h3>
            
            <p style={{ 
              color: '#6B7280', 
              marginBottom: '24px',
              fontSize: '14px',
              lineHeight: '1.5'
            }}>
              We encountered an unexpected error while loading your premium dashboard.
            </p>
            
            <div style={{
              display: 'flex',
              gap: '12px',
              flexDirection: 'column'
            }}>
              <button 
                onClick={() => window.location.reload()}
                style={{
                  background: 'linear-gradient(135deg, #007AFF 0%, #3D9DFF 100%)',
                  color: 'white',
                  border: 'none',
                  padding: '12px 24px',
                  borderRadius: '12px',
                  cursor: 'pointer',
                  fontSize: '14px',
                  fontWeight: '600',
                  transition: 'all 0.3s ease'
                }}
                onMouseOver={(e) => {
                  e.target.style.transform = 'translateY(-2px)';
                  e.target.style.boxShadow = '0 10px 15px -3px rgba(0, 122, 255, 0.3)';
                }}
                onMouseOut={(e) => {
                  e.target.style.transform = 'translateY(0)';
                  e.target.style.boxShadow = 'none';
                }}
              >
                Reload Dashboard
              </button>
              
              <button 
                onClick={() => window.history.back()}
                style={{
                  background: 'rgba(107, 114, 128, 0.1)',
                  color: '#4B5563',
                  border: 'none',
                  padding: '12px 24px',
                  borderRadius: '12px',
                  cursor: 'pointer',
                  fontSize: '14px',
                  fontWeight: '600'
                }}
              >
                Go Back
              </button>
            </div>
            
            {process.env.NODE_ENV === 'development' && this.state.errorInfo && (
              <details style={{
                marginTop: '20px',
                textAlign: 'left',
                fontSize: '12px',
                color: '#6B7280'
              }}>
                <summary style={{ cursor: 'pointer', marginBottom: '8px' }}>
                  Error Details (Development)
                </summary>
                <pre style={{
                  background: '#F3F4F6',
                  padding: '12px',
                  borderRadius: '8px',
                  overflow: 'auto',
                  maxHeight: '200px'
                }}>
                  {this.state.error && this.state.error.toString()}
                  <br />
                  {this.state.errorInfo.componentStack}
                </pre>
              </details>
            )}
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Main Premium App Component
function PremiumApp() {
  return (
    <PremiumErrorBoundary>
      <PremiumParamedisDashboard />
    </PremiumErrorBoundary>
  );
}

// Enhanced Initialization with Performance Monitoring
function initializePremiumDashboard() {
  const container = document.getElementById('premium-dashboard-root');
  
  if (!container) {
    console.error('Premium Dashboard: Root container #premium-dashboard-root not found');
    return;
  }

  // Performance monitoring
  const startTime = performance.now();
  
  // Create React root with concurrent features
  const root = createRoot(container, {
    // Enable concurrent features for better performance
    unstable_strictMode: true
  });

  // Render with performance tracking
  root.render(<PremiumApp />);
  
  // Log performance metrics
  if (window.requestIdleCallback) {
    window.requestIdleCallback(() => {
      const endTime = performance.now();
      console.log(`Premium Dashboard initialized in ${endTime - startTime}ms`);
    });
  }
  
  // Add global error handler for unhandled promises
  window.addEventListener('unhandledrejection', event => {
    console.error('Premium Dashboard: Unhandled promise rejection:', event.reason);
  });
}

// Service Worker Registration for PWA capabilities
function registerServiceWorker() {
  if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/premium-dashboard-sw.js')
        .then(registration => {
          console.log('Premium Dashboard: SW registered successfully');
        })
        .catch(registrationError => {
          console.log('Premium Dashboard: SW registration failed');
        });
    });
  }
}

// Enhanced DOM Ready Handler
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializePremiumDashboard);
} else {
  initializePremiumDashboard();
}

// Initialize PWA features
registerServiceWorker();

// Hot Module Replacement for development
if (import.meta.hot) {
  import.meta.hot.accept('./components/PremiumParamedisDashboard.jsx', () => {
    console.log('Premium Dashboard: Hot reloading...');
  });
}

// Export for potential external usage
export { PremiumParamedisDashboard as default };