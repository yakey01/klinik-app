<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class FaceRecognition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'face_encoding',
        'face_landmarks',
        'face_image_path',
        'confidence_score',
        'encoding_algorithm',
        'is_active',
        'is_verified',
        'verified_at',
        'verified_by',
        'metadata',
    ];

    protected $casts = [
        'face_landmarks' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:4',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verified by relationship
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get face image URL
     */
    public function getFaceImageUrlAttribute(): ?string
    {
        return $this->face_image_path ? Storage::url($this->face_image_path) : null;
    }

    /**
     * Verify face recognition
     */
    public function verify(int $verifiedBy): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }

    /**
     * Compare face encodings
     */
    public static function compareFaces(string $encoding1, string $encoding2, float $threshold = 0.6): bool
    {
        // Simple comparison - in production, use proper face recognition library
        $similarity = 1 - (levenshtein($encoding1, $encoding2) / max(strlen($encoding1), strlen($encoding2)));
        return $similarity >= $threshold;
    }

    /**
     * Generate face encoding (placeholder)
     */
    public static function generateEncoding(string $imagePath): array
    {
        // In production, integrate with face recognition service
        return [
            'encoding' => base64_encode(hash('sha256', $imagePath . time())),
            'landmarks' => [
                'left_eye' => ['x' => 120, 'y' => 80],
                'right_eye' => ['x' => 180, 'y' => 80],
                'nose' => ['x' => 150, 'y' => 120],
                'mouth' => ['x' => 150, 'y' => 160],
            ],
            'confidence' => 0.95
        ];
    }

    /**
     * Scope for active face recognitions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified face recognitions
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
