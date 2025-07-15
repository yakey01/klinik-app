<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'is_readonly',
        'validation_rules',
        'meta',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_readonly' => 'boolean',
        'validation_rules' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get a setting value with caching
     */
    public static function get($key, $default = null)
    {
        $cacheKey = 'system_setting_' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget('system_setting_' . $key);

        return $setting;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup($group)
    {
        return self::where('group', $group)->get()->pluck('value', 'key');
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'object':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'array':
            case 'object':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Get public settings (can be accessed without authentication)
     */
    public static function getPublicSettings()
    {
        return self::where('is_public', true)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => self::castValue($setting->value, $setting->type)];
        });
    }

    /**
     * Clear all setting caches
     */
    public static function clearCache()
    {
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('system_setting_' . $key);
        }
    }
}
