<?php

namespace App\Helpers;

use App\Services\LocalizationService;
use NumberFormatter;
use Exception;

class CurrencyHelper
{
    protected static LocalizationService $localizationService;
    
    public static function init(): void
    {
        if (!isset(self::$localizationService)) {
            self::$localizationService = app(LocalizationService::class);
        }
    }
    
    /**
     * Format currency amount with locale-specific formatting
     */
    public static function format(float $amount, string $locale = null, bool $showSymbol = true): string
    {
        self::init();
        return self::$localizationService->formatCurrency($amount, $locale, $showSymbol);
    }
    
    /**
     * Format number with locale-specific formatting
     */
    public static function formatNumber(float $number, string $locale = null, int $decimals = null): string
    {
        self::init();
        return self::$localizationService->formatNumber($number, $locale, $decimals);
    }
    
    /**
     * Format percentage with locale-specific formatting
     */
    public static function formatPercentage(float $percentage, string $locale = null, int $decimals = 2): string
    {
        self::init();
        $config = self::$localizationService->getLocaleConfig($locale);
        
        $formattedNumber = number_format(
            $percentage,
            $decimals,
            $config['number_format']['decimal_separator'],
            $config['number_format']['thousands_separator']
        );
        
        return $formattedNumber . '%';
    }
    
    /**
     * Convert amount to millions format for display
     */
    public static function toMillions(float $amount, string $locale = null, int $decimals = 1): string
    {
        self::init();
        $millions = $amount / 1000000;
        $formatted = self::$localizationService->formatNumber($millions, $locale, $decimals);
        
        return match ($locale ?? self::$localizationService->getCurrentLocale()) {
            'id' => $formatted . ' Jt',
            'en' => $formatted . 'M',
            'ar' => $formatted . ' مليون',
            'ms' => $formatted . ' Jt',
            default => $formatted . 'M',
        };
    }
    
    /**
     * Convert amount to thousands format for display
     */
    public static function toThousands(float $amount, string $locale = null, int $decimals = 0): string
    {
        self::init();
        $thousands = $amount / 1000;
        $formatted = self::$localizationService->formatNumber($thousands, $locale, $decimals);
        
        return match ($locale ?? self::$localizationService->getCurrentLocale()) {
            'id' => $formatted . ' Rb',
            'en' => $formatted . 'K',
            'ar' => $formatted . ' ألف',
            'ms' => $formatted . ' Rb',
            default => $formatted . 'K',
        };
    }
    
    /**
     * Smart format - automatically choose best unit
     */
    public static function smartFormat(float $amount, string $locale = null): string
    {
        if (abs($amount) >= 1000000000) {
            return self::toBillions($amount, $locale);
        } elseif (abs($amount) >= 1000000) {
            return self::toMillions($amount, $locale);
        } elseif (abs($amount) >= 1000) {
            return self::toThousands($amount, $locale);
        } else {
            return self::format($amount, $locale, false);
        }
    }
    
    /**
     * Convert amount to billions format for display
     */
    public static function toBillions(float $amount, string $locale = null, int $decimals = 2): string
    {
        self::init();
        $billions = $amount / 1000000000;
        $formatted = self::$localizationService->formatNumber($billions, $locale, $decimals);
        
        return match ($locale ?? self::$localizationService->getCurrentLocale()) {
            'id' => $formatted . ' M',
            'en' => $formatted . 'B',
            'ar' => $formatted . ' مليار',
            'ms' => $formatted . ' B',
            default => $formatted . 'B',
        };
    }
    
    /**
     * Get currency symbol for locale
     */
    public static function getCurrencySymbol(string $locale = null): string
    {
        self::init();
        $config = self::$localizationService->getLocaleConfig($locale);
        
        return match ($config['currency']) {
            'IDR' => 'Rp',
            'USD' => '$',
            'SAR' => 'ريال',
            'MYR' => 'RM',
            default => $config['currency'],
        };
    }
    
    /**
     * Parse currency string to float
     */
    public static function parse(string $currencyString, string $locale = null): float
    {
        self::init();
        $config = self::$localizationService->getLocaleConfig($locale);
        
        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^\d.,\-]/', '', $currencyString);
        
        // Handle different decimal separators
        if ($config['number_format']['decimal_separator'] === ',') {
            // Replace thousands separator first, then decimal separator
            $cleaned = str_replace($config['number_format']['thousands_separator'], '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            // Remove thousands separator
            $cleaned = str_replace($config['number_format']['thousands_separator'], '', $cleaned);
        }
        
        return (float) $cleaned;
    }
    
    /**
     * Format difference between two amounts
     */
    public static function formatDifference(float $current, float $previous, string $locale = null): array
    {
        $difference = $current - $previous;
        $percentage = $previous != 0 ? (($difference / $previous) * 100) : 0;
        
        return [
            'amount' => self::format(abs($difference), $locale),
            'percentage' => self::formatPercentage(abs($percentage), $locale),
            'direction' => $difference >= 0 ? 'up' : 'down',
            'is_positive' => $difference >= 0,
            'raw_difference' => $difference,
            'raw_percentage' => $percentage,
        ];
    }
    
    /**
     * Format growth rate
     */
    public static function formatGrowthRate(float $growthRate, string $locale = null): string
    {
        $formatted = self::formatPercentage(abs($growthRate), $locale);
        $sign = $growthRate >= 0 ? '+' : '-';
        
        return $sign . $formatted;
    }
    
    /**
     * Get localized number formatter
     */
    public static function getNumberFormatter(string $locale = null): NumberFormatter
    {
        self::init();
        $locale = $locale ?? self::$localizationService->getCurrentLocale();
        
        // Map our locale codes to ICU locale codes
        $icuLocale = match ($locale) {
            'id' => 'id_ID',
            'en' => 'en_US', 
            'ar' => 'ar_SA',
            'ms' => 'ms_MY',
            default => 'en_US',
        };
        
        try {
            return new NumberFormatter($icuLocale, NumberFormatter::CURRENCY);
        } catch (Exception $e) {
            // Fallback to default locale
            return new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        }
    }
    
    /**
     * Format currency with advanced NumberFormatter
     */
    public static function formatAdvanced(float $amount, string $locale = null): string
    {
        try {
            $formatter = self::getNumberFormatter($locale);
            return $formatter->formatCurrency($amount, self::getCurrencyCode($locale));
        } catch (Exception $e) {
            // Fallback to basic formatting
            return self::format($amount, $locale);
        }
    }
    
    /**
     * Get currency code for locale
     */
    protected static function getCurrencyCode(string $locale = null): string
    {
        self::init();
        $config = self::$localizationService->getLocaleConfig($locale);
        return $config['currency'];
    }
}