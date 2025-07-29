<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakanValidationHistory extends Model
{
    use HasFactory;

    protected $table = 'tindakan_validation_history';

    protected $fillable = [
        'tindakan_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'submitted' => 'Diajukan untuk Validasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'revised' => 'Diminta Revisi',
            'commented' => 'Diberi Komentar',
            default => ucfirst($this->action)
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->new_status) {
            'pending' => 'warning',
            'disetujui' => 'success',
            'ditolak' => 'danger',
            'revisi' => 'info',
            default => 'gray'
        };
    }
}