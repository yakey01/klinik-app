<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'created_by'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isWithinGeofence(float $userLatitude, float $userLongitude): bool
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($userLatitude);
        $deltaLat = deg2rad($userLatitude - $this->latitude);
        $deltaLon = deg2rad($userLongitude - $this->longitude);
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $distance = $earthRadius * $c; // Distance in meters
        
        return $distance <= $this->radius;
    }

    public function getDistanceFrom(float $userLatitude, float $userLongitude): float
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($userLatitude);
        $deltaLat = deg2rad($userLatitude - $this->latitude);
        $deltaLon = deg2rad($userLongitude - $this->longitude);
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c; // Distance in meters
    }
}