import React from 'react';
import { createRoot } from 'react-dom/client';
import ParamedisJaspelDashboard from './components/ParamedisJaspelDashboard.jsx';

// Error Boundary Component
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('React Error Boundary caught an error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          minHeight: '100vh',
          padding: '20px',
          textAlign: 'center',
          background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)'
        }}>
          <div style={{
            background: '#fee2e2',
            border: '1px solid #fecaca',
            borderRadius: '12px',
            padding: '20px',
            maxWidth: '400px'
          }}>
            <h3 style={{ color: '#dc2626', margin: '0 0 10px 0' }}>
              React Component Error
            </h3>
            <p style={{ color: '#7f1d1d', margin: '0 0 15px 0' }}>
              Terjadi kesalahan pada komponen React.
            </p>
            <button 
              onClick={() => window.location.reload()}
              style={{
                background: '#dc2626',
                color: 'white',
                border: 'none',
                padding: '10px 20px',
                borderRadius: '8px',
                cursor: 'pointer'
              }}
            >
              Muat Ulang
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Main App Component
function App() {
  return (
    <ErrorBoundary>
      <ParamedisJaspelDashboard />
    </ErrorBoundary>
  );
}

// Initialize React App
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('paramedis-jaspel-root');
  
  if (container) {
    const root = createRoot(container);
    root.render(<App />);
  } else {
    console.error('Root container #paramedis-jaspel-root not found');
  }
});

// Hot Module Replacement for development
if (import.meta.hot) {
  import.meta.hot.accept();
}