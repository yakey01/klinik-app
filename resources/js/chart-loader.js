/**
 * Chart.js Auto Loader
 * Loads Chart.js with auto configuration for compatibility with legacy dashboard components
 */

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement, 
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  RadialLinearScale,
  Filler
} from 'chart.js/auto';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,  
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  RadialLinearScale,
  Filler
);

// Make Chart.js available globally for legacy components
window.Chart = ChartJS;

// Dispatch custom event to signal Chart.js is loaded
document.dispatchEvent(new CustomEvent('chartjs-loaded'));

// Export Chart.js for ES6 imports
export default ChartJS;