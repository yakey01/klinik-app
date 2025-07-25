<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Add conditional fields to response
     */
    protected function whenLoaded(string $relationship, mixed $value = null): mixed
    {
        if ($value === null) {
            $value = $this->{$relationship};
        }

        return $this->when($this->relationLoaded($relationship), $value);
    }

    /**
     * Format currency for Indonesian Rupiah
     */
    protected function formatCurrency(?float $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format date for Indonesian locale
     */
    protected function formatDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        return \Carbon\Carbon::parse($date)->format('d M Y');
    }

    /**
     * Format datetime for Indonesian locale
     */
    protected function formatDateTime(?string $datetime): ?string
    {
        if (!$datetime) {
            return null;
        }

        return \Carbon\Carbon::parse($datetime)->format('d M Y H:i');
    }

    /**
     * Get time ago format
     */
    protected function timeAgo(?string $datetime): ?string
    {
        if (!$datetime) {
            return null;
        }

        return \Carbon\Carbon::parse($datetime)->diffForHumans();
    }

    /**
     * Safely get nested attribute
     */
    protected function safeGet(string $path, mixed $default = null): mixed
    {
        $value = $this->resource;
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (is_object($value) && isset($value->{$key})) {
                $value = $value->{$key};
            } elseif (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }
}