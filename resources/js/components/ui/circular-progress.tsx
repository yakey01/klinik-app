import React from 'react';
import { motion } from 'framer-motion';

interface CircularProgressProps {
  value: number;
  size?: 'sm' | 'md' | 'lg';
  showValue?: boolean;
  color?: string;
  backgroundColor?: string;
  strokeWidth?: number;
  className?: string;
}

export function CircularProgress({ 
  value, 
  size = 'md',
  showValue = true,
  color = 'hsl(var(--primary))',
  backgroundColor = 'hsl(var(--muted))',
  strokeWidth,
  className = ''
}: CircularProgressProps) {
  const sizes = {
    sm: { width: 60, height: 60, stroke: 4, fontSize: '14px' },
    md: { width: 80, height: 80, stroke: 6, fontSize: '18px' },
    lg: { width: 120, height: 120, stroke: 8, fontSize: '24px' }
  };
  
  const { width, height, stroke, fontSize } = sizes[size];
  const actualStroke = strokeWidth || stroke;
  const radius = (width - actualStroke) / 2;
  const circumference = radius * 2 * Math.PI;
  const offset = circumference - (value / 100) * circumference;
  
  return (
    <div className={`circular-progress ${className}`} style={{ width, height }}>
      <svg width={width} height={height} className="transform -rotate-90">
        {/* Background circle */}
        <circle
          cx={width / 2}
          cy={height / 2}
          r={radius}
          stroke={backgroundColor}
          strokeWidth={actualStroke}
          fill="none"
        />
        {/* Progress circle */}
        <motion.circle
          cx={width / 2}
          cy={height / 2}
          r={radius}
          stroke={color}
          strokeWidth={actualStroke}
          fill="none"
          strokeLinecap="round"
          strokeDasharray={circumference}
          initial={{ strokeDashoffset: circumference }}
          animate={{ strokeDashoffset: offset }}
          transition={{ duration: 0.5, ease: "easeOut" }}
        />
      </svg>
      {showValue && (
        <div 
          className="absolute inset-0 flex items-center justify-center"
          style={{ fontSize }}
        >
          <span className="font-semibold text-gray-900 dark:text-white">
            {value}%
          </span>
        </div>
      )}
    </div>
  );
}