<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FeatureFlag extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'conditions',
        'environment',
        'starts_at',
        'ends_at',
        'meta',
        'is_permanent',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_permanent' => 'boolean',
        'conditions' => 'array',
        'meta' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Check if a feature is enabled
     */
    public static function isEnabled($key, $user = null)
    {
        $cacheKey = 'feature_flag_' . $key;
        
        $flag = Cache::remember($cacheKey, 600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$flag) {
            return false;
        }

        // Check if feature is globally disabled
        if (!$flag->is_enabled) {
            return false;
        }

        // Check environment
        if ($flag->environment && $flag->environment !== config('app.env')) {
            return false;
        }

        // Check time constraints
        $now = Carbon::now();
        if ($flag->starts_at && $now->lt($flag->starts_at)) {
            return false;
        }
        if ($flag->ends_at && $now->gt($flag->ends_at)) {
            return false;
        }

        // Check user/role conditions
        if ($flag->conditions && $user) {
            return self::checkConditions($flag->conditions, $user);
        }

        return true;
    }

    /**
     * Check user/role conditions
     */
    protected static function checkConditions($conditions, $user)
    {
        // Check user IDs
        if (isset($conditions['users']) && is_array($conditions['users'])) {
            if (in_array($user->id, $conditions['users'])) {
                return true;
            }
        }

        // Check roles
        if (isset($conditions['roles']) && is_array($conditions['roles'])) {
            if ($user->hasAnyRole($conditions['roles'])) {
                return true;
            }
        }

        // Check percentage rollout
        if (isset($conditions['percentage']) && is_numeric($conditions['percentage'])) {
            $hash = hexdec(substr(md5($user->id), 0, 8));
            $percentage = ($hash % 100) + 1;
            if ($percentage <= $conditions['percentage']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable a feature flag
     */
    public static function enable($key)
    {
        $flag = self::where('key', $key)->first();
        if ($flag && !$flag->is_permanent) {
            $flag->update(['is_enabled' => true]);
            Cache::forget('feature_flag_' . $key);
        }
    }

    /**
     * Disable a feature flag
     */
    public static function disable($key)
    {
        $flag = self::where('key', $key)->first();
        if ($flag && !$flag->is_permanent) {
            $flag->update(['is_enabled' => false]);
            Cache::forget('feature_flag_' . $key);
        }
    }

    /**
     * Get all enabled features for a user
     */
    public static function getEnabledFeatures($user = null)
    {
        return self::where('is_enabled', true)
            ->get()
            ->filter(function ($flag) use ($user) {
                return self::isEnabled($flag->key, $user);
            })
            ->pluck('key');
    }

    /**
     * Clear all feature flag caches
     */
    public static function clearCache()
    {
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('feature_flag_' . $key);
        }
    }
}
