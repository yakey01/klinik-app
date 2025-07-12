<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class JadwalJaga extends Model
{
    protected $fillable = [
        'tanggal_jaga',
        'shift_template_id',
        'pegawai_id',
        'unit_instalasi', // Keep for backward compatibility
        'unit_kerja', // New field
        'peran',
        'status_jaga',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_jaga' => 'date',
    ];

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    // For FullCalendar integration
    public function getStartAttribute(): string
    {
        return $this->tanggal_jaga->format('Y-m-d') . 'T' . $this->shiftTemplate->jam_masuk;
    }

    public function getEndAttribute(): string
    {
        $endDate = $this->tanggal_jaga;
        $endTime = $this->shiftTemplate->jam_pulang;
        
        // Handle overnight shifts
        if ($this->shiftTemplate->jam_pulang < $this->shiftTemplate->jam_masuk) {
            $endDate = $endDate->addDay();
        }
        
        return $endDate->format('Y-m-d') . 'T' . $endTime;
    }

    public function getTitleAttribute(): string
    {
        return $this->pegawai->name . ' (' . $this->shiftTemplate->nama_shift . ')';
    }

    public function getColorAttribute(): string
    {
        return match($this->status_jaga) {
            'Aktif' => '#10b981', // green
            'Cuti' => '#f59e0b',  // amber
            'Izin' => '#ef4444',  // red
            'OnCall' => '#3b82f6', // blue
            default => '#6b7280'   // gray
        };
    }

    // Conflict validation - check for same shift on same day (allow different shifts)
    public function hasConflict(): bool
    {
        return static::where('pegawai_id', $this->pegawai_id)
            ->where('tanggal_jaga', $this->tanggal_jaga)
            ->where('shift_template_id', $this->shift_template_id) // Only conflict if same shift
            ->where('id', '!=', $this->id ?? 0)
            ->exists();
    }

    // Check if user can be assigned to this shift
    public function canAssignStaff(): bool
    {
        // Allow multiple shifts per day, but not the same shift
        return !$this->hasConflict();
    }

    // Get other shifts for this staff on the same day
    public function getOtherShiftsOnSameDay()
    {
        return static::where('pegawai_id', $this->pegawai_id)
            ->where('tanggal_jaga', $this->tanggal_jaga)
            ->where('shift_template_id', '!=', $this->shift_template_id)
            ->where('id', '!=', $this->id ?? 0)
            ->with('shiftTemplate')
            ->get();
    }

    // Get available staff based on unit type
    public static function getAvailableStaffForUnit($unit_kerja)
    {
        if ($unit_kerja === 'Dokter Jaga') {
            // Get users with dokter role
            return User::whereHas('roles', function ($query) {
                $query->where('name', 'dokter');
            })->get();
        } else {
            // Get users with non-dokter roles (petugas, paramedis, etc.)
            return User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['petugas', 'paramedis', 'bendahara', 'admin']);
            })->get();
        }
    }
}
