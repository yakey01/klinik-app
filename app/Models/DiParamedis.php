<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DiParamedis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'di_paramedis';

    protected $fillable = [
        'pegawai_id',
        'user_id',
        'jadwal_jaga_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'shift',
        'lokasi_tugas',
        
        // Patient care activities
        'jumlah_pasien_dilayani',
        'jumlah_tindakan_medis',
        'jumlah_observasi_pasien',
        
        // Medical procedures
        'tindakan_medis',
        'obat_diberikan',
        'alat_medis_digunakan',
        
        // Emergency response
        'jumlah_kasus_emergency',
        'catatan_kasus_emergency',
        
        // Documentation
        'laporan_kegiatan',
        'kendala_hambatan',
        'saran_perbaikan',
        
        // Status and validation
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        
        // Additional fields
        'signature_path',
        'attachments',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i:s',
        'jam_selesai' => 'datetime:H:i:s',
        'tindakan_medis' => 'array',
        'obat_diberikan' => 'array',
        'alat_medis_digunakan' => 'array',
        'attachments' => 'array',
        'approved_at' => 'datetime',
        'jumlah_pasien_dilayani' => 'integer',
        'jumlah_tindakan_medis' => 'integer',
        'jumlah_observasi_pasien' => 'integer',
        'jumlah_kasus_emergency' => 'integer',
    ];

    // Relationships
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwalJaga(): BelongsTo
    {
        return $this->belongsTo(JadwalJaga::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByPegawai($query, $pegawaiId)
    {
        return $query->where('pegawai_id', $pegawaiId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    // Attributes
    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function getCanBeApprovedAttribute(): bool
    {
        return $this->status === 'submitted';
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return null;
        }

        $start = Carbon::parse($this->jam_mulai);
        $end = Carbon::parse($this->jam_selesai);
        
        return $end->diff($start)->format('%H:%I');
    }

    public function getTotalActivitiesAttribute(): int
    {
        return $this->jumlah_pasien_dilayani + 
               $this->jumlah_tindakan_medis + 
               $this->jumlah_observasi_pasien + 
               $this->jumlah_kasus_emergency;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'submitted' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };
    }

    // Methods
    public function submit(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->update([
            'status' => 'submitted',
            'jam_selesai' => $this->jam_selesai ?: now()->format('H:i:s')
        ]);

        return true;
    }

    public function approve(int $userId): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => null
        ]);

        return true;
    }

    public function reject(int $userId, string $reason): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);

        return true;
    }

    public function addTindakanMedis(array $tindakan): void
    {
        $current = $this->tindakan_medis ?? [];
        $current[] = array_merge($tindakan, ['timestamp' => now()->toIso8601String()]);
        
        $this->update([
            'tindakan_medis' => $current,
            'jumlah_tindakan_medis' => count($current)
        ]);
    }

    public function addObatDiberikan(array $obat): void
    {
        $current = $this->obat_diberikan ?? [];
        $current[] = array_merge($obat, ['timestamp' => now()->toIso8601String()]);
        
        $this->update(['obat_diberikan' => $current]);
    }

    public function addAlatMedis(array $alat): void
    {
        $current = $this->alat_medis_digunakan ?? [];
        $current[] = array_merge($alat, ['timestamp' => now()->toIso8601String()]);
        
        $this->update(['alat_medis_digunakan' => $current]);
    }

    // Validation rules
    public static function validationRules(): array
    {
        return [
            'pegawai_id' => 'required|exists:pegawai,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i:s',
            'jam_selesai' => 'nullable|date_format:H:i:s|after:jam_mulai',
            'lokasi_tugas' => 'required|string|max:255',
            'jumlah_pasien_dilayani' => 'required|integer|min:0',
            'jumlah_tindakan_medis' => 'required|integer|min:0',
            'jumlah_observasi_pasien' => 'required|integer|min:0',
            'jumlah_kasus_emergency' => 'required|integer|min:0',
            'laporan_kegiatan' => 'nullable|string|max:5000',
            'kendala_hambatan' => 'nullable|string|max:2000',
            'saran_perbaikan' => 'nullable|string|max:2000',
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->id();
                
                // Auto-fill pegawai_id from user's pegawai relationship if not set
                if (!$model->pegawai_id && auth()->user()->pegawai_id) {
                    $model->pegawai_id = auth()->user()->pegawai_id;
                }
            }
        });
    }
}