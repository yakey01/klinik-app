<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_config_{$key}", 3600, function () use ($key, $default) {
            $config = static::where('key', $key)->where('is_active', true)->first();
            return $config ? $config->value : $default;
        });
    }

    public static function set(string $key, $value, string $category = 'general', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'category' => $category,
                'description' => $description,
                'is_active' => true,
            ]
        );

        Cache::forget("system_config_{$key}");
    }

    public static function getAllByCategory(string $category): array
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->pluck('value', 'key')
            ->toArray();
    }
}
