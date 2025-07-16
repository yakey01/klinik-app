<?php

namespace App\Services;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCodeGeneratorService
{
    /**
     * Generate next code for Pendapatan with format PND-0001
     */
    public static function generatePendapatanCode(): string
    {
        $prefix = 'PND';
        $separator = '-';
        $padding = 4;
        
        return static::generateCode('pendapatan', 'kode_pendapatan', $prefix, $separator, $padding);
    }
    
    /**
     * Generate next code for Pengeluaran with format PNG-0001
     */
    public static function generatePengeluaranCode(): string
    {
        $prefix = 'PNG';
        $separator = '-';
        $padding = 4;
        
        return static::generateCode('pengeluaran', 'kode_pengeluaran', $prefix, $separator, $padding);
    }
    
    /**
     * Generate next sequential code with transaction safety
     */
    private static function generateCode(string $table, string $column, string $prefix, string $separator, int $padding): string
    {
        return DB::transaction(function() use ($table, $column, $prefix, $separator, $padding) {
            try {
                // For better compatibility, use SELECT FOR UPDATE instead of table locking
                $latestRecord = DB::table($table)
                    ->where($column, 'LIKE', "{$prefix}{$separator}%")
                    ->orderByRaw("CAST(SUBSTR({$column}, " . (strlen($prefix) + strlen($separator) + 1) . ") AS INTEGER) DESC")
                    ->lockForUpdate()
                    ->first();
                
                // Extract the numeric part and increment
                $nextNumber = 1;
                if ($latestRecord) {
                    $numericPart = str_replace($prefix . $separator, '', $latestRecord->{$column});
                    $nextNumber = intval($numericPart) + 1;
                }
                
                // Generate the new code
                $newCode = $prefix . $separator . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
                
                // Double-check that the generated code doesn't exist (extra safety)
                $exists = DB::table($table)->where($column, $newCode)->exists();
                if ($exists) {
                    // If by any chance the code exists, recursively try again
                    Log::warning("Generated code {$newCode} already exists, retrying...");
                    return static::generateCode($table, $column, $prefix, $separator, $padding);
                }
                
                return $newCode;
                
            } catch (\Exception $e) {
                Log::error("Error generating code for {$table}: " . $e->getMessage());
                throw $e;
            }
        });
    }
    
    /**
     * Alternative method using UUID-based approach for extreme safety
     */
    public static function generateUniqueCode(string $table, string $column, string $prefix, string $separator = '-', int $padding = 4): string
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            try {
                $nextNumber = static::getNextSequentialNumber($table, $column, $prefix, $separator);
                $newCode = $prefix . $separator . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
                
                // Check if code already exists
                $exists = DB::table($table)->where($column, $newCode)->exists();
                if (!$exists) {
                    return $newCode;
                }
                
                $attempts++;
                
            } catch (\Exception $e) {
                Log::error("Error in generateUniqueCode attempt {$attempts}: " . $e->getMessage());
                $attempts++;
            }
        }
        
        // Fallback to UUID-based code if all attempts fail
        return $prefix . $separator . substr(str_replace('-', '', \Illuminate\Support\Str::uuid()), 0, $padding);
    }
    
    /**
     * Get the next sequential number for a given prefix
     */
    private static function getNextSequentialNumber(string $table, string $column, string $prefix, string $separator): int
    {
        // Use raw query with proper ordering to get the highest number
        $latestCode = DB::table($table)
            ->where($column, 'LIKE', "{$prefix}{$separator}%")
            ->orderByRaw("LENGTH({$column}) DESC, {$column} DESC")
            ->value($column);
        
        if (!$latestCode) {
            return 1;
        }
        
        // Extract numeric part
        $numericPart = str_replace($prefix . $separator, '', $latestCode);
        
        // Remove leading zeros and convert to integer
        $currentNumber = intval($numericPart);
        
        return $currentNumber + 1;
    }
    
    /**
     * Validate if a code follows the expected format
     */
    public static function validateCodeFormat(string $code, string $prefix, string $separator = '-', int $padding = 4): bool
    {
        $pattern = '/^' . preg_quote($prefix . $separator, '/') . '\d{' . $padding . '}$/';
        return preg_match($pattern, $code) === 1;
    }
    
    /**
     * Get the next available code for any table/column/prefix combination
     */
    public static function getNextCode(string $table, string $column, string $prefix, string $separator = '-', int $padding = 4): string
    {
        return static::generateCode($table, $column, $prefix, $separator, $padding);
    }
}