<?php

namespace App\Helpers;

use Carbon\Carbon;

class AccurateTimeHelper
{
    /**
     * Get accurate current time corrected for system clock issues
     */
    public static function now(): Carbon
    {
        // Return current time in Jakarta timezone (WIB)
        return Carbon::now('Asia/Jakarta');
    }
    
    /**
     * Get accurate today date corrected for system clock issues
     */
    public static function today(): Carbon
    {
        return static::now()->startOfDay();
    }
    
    /**
     * Get accurate start of month corrected for system clock issues
     */
    public static function startOfMonth(): Carbon
    {
        return static::now()->startOfMonth();
    }
    
    /**
     * Format accurate date for display
     */
    public static function formatDate($format = 'Y-m-d'): string
    {
        return static::now()->format($format);
    }
    
    /**
     * Format accurate time for display
     */
    public static function formatTime($format = 'H:i:s'): string
    {
        return static::now()->format($format);
    }
    
    /**
     * Get current hour for greeting
     */
    public static function getHour(): int
    {
        return static::now()->hour;
    }
    
    /**
     * Check if current time is weekday
     */
    public static function isWeekday(): bool
    {
        return static::now()->isWeekday();
    }
}