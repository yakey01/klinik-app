import React from 'react';

interface SparklineProps {
  data: number[];
  color?: string;
  showArea?: boolean;
  height?: number;
  width?: number;
  className?: string;
}

export function Sparkline({ 
  data, 
  color = 'hsl(var(--primary))',
  showArea = true,
  height = 30,
  width = 100,
  className = ''
}: SparklineProps) {
  if (!data || data.length === 0) return null;
  
  const min = Math.min(...data);
  const max = Math.max(...data);
  const range = max - min || 1;
  
  // Create points for the line
  const points = data.map((value, index) => {
    const x = (index / (data.length - 1)) * width;
    const y = height - ((value - min) / range) * height;
    return `${x},${y}`;
  }).join(' ');
  
  // Create path for the area
  const areaPath = `M0,${height} L${points} L${width},${height} Z`;
  
  return (
    <div className={`sparkline ${className}`}>
      <svg 
        width={width} 
        height={height} 
        className="sparkline-svg"
        viewBox={`0 0 ${width} ${height}`}
        preserveAspectRatio="none"
      >
        {showArea && (
          <path
            d={areaPath}
            fill={color}
            fillOpacity="0.1"
            className="sparkline-area"
          />
        )}
        <polyline
          points={points}
          fill="none"
          stroke={color}
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
          className="sparkline-path"
        />
      </svg>
    </div>
  );
}