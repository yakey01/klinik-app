import React from 'react';
import { createRoot } from 'react-dom/client';
import ParamedisDashboard from './components/ParamedisDashboard';
import './styles/ParamedisDashboard.css';

// Initialize React component when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('paramedis-dashboard-root');
  if (container) {
    const root = createRoot(container);
    root.render(<ParamedisDashboard />);
  }
});

// Hot Module Replacement for development
if (import.meta.hot) {
  import.meta.hot.accept('./components/ParamedisDashboard', () => {
    const UpdatedComponent = require('./components/ParamedisDashboard').default;
    const container = document.getElementById('paramedis-dashboard-root');
    if (container) {
      const root = createRoot(container);
      root.render(<UpdatedComponent />);
    }
  });
}