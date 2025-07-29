import React from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { motion } from 'framer-motion';

interface TrendIndicatorProps {
  value: number;
  previousValue?: number;
  format?: 'percentage' | 'currency' | 'number';
  size?: 'sm' | 'md' | 'lg';
  showIcon?: boolean;
  className?: string;
}

export function TrendIndicator({ 
  value, 
  previousValue,
  format = 'percentage',
  size = 'md',
  showIcon = true,
  className = ''
}: TrendIndicatorProps) {
  const change = previousValue ? ((value - previousValue) / previousValue) * 100 : 0;
  const isPositive = change > 0;
  const isNegative = change < 0;
  const isNeutral = change === 0;
  
  const sizes = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base'
  };
  
  const iconSizes = {
    sm: 'w-3 h-3',
    md: 'w-4 h-4',
    lg: 'w-5 h-5'
  };
  
  const formatValue = () => {
    if (format === 'percentage') {
      return `${isPositive ? '+' : ''}${change.toFixed(1)}%`;
    } else if (format === 'currency') {
      const diff = value - (previousValue || 0);
      return `${isPositive ? '+' : ''}Rp ${Math.abs(diff).toLocaleString('id-ID')}`;
    } else {
      const diff = value - (previousValue || 0);
      return `${isPositive ? '+' : ''}${diff}`;
    }
  };
  
  const colorClass = isPositive 
    ? 'text-green-600 dark:text-green-400' 
    : isNegative 
    ? 'text-red-600 dark:text-red-400' 
    : 'text-gray-600 dark:text-gray-400';
  
  return (
    <motion.div 
      className={`trend-indicator flex items-center gap-1 ${sizes[size]} ${colorClass} ${className}`}
      initial={{ opacity: 0, scale: 0.8 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={{ duration: 0.3 }}
    >
      {showIcon && (
        <>
          {isPositive && <TrendingUp className={iconSizes[size]} />}
          {isNegative && <TrendingDown className={iconSizes[size]} />}
          {isNeutral && <Minus className={iconSizes[size]} />}
        </>
      )}
      <span className="font-medium">
        {formatValue()}
      </span>
    </motion.div>
  );
}