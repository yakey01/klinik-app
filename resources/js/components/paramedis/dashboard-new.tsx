import { createRoot } from 'react-dom/client';
import { JaspelDashboardCard } from './JaspelDashboardCard';

function DashboardApp() {
  return (
    <div className="space-y-6">
      <JaspelDashboardCard />
    </div>
  );
}

// Mount the component when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  const rootElement = document.getElementById('jaspel-dashboard-root');
  if (rootElement) {
    const root = createRoot(rootElement);
    root.render(<DashboardApp />);
  }
});