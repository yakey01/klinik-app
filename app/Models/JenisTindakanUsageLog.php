<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisTindakanUsageLog extends Model
{
    use HasFactory;

    protected $table = 'jenis_tindakan_usage_log';

    protected $fillable = [
        'jenis_tindakan_id',
        'tindakan_id',
        'used_by',
        'tarif_at_usage',
        'jaspel_percentage_at_usage',
        'master_data_snapshot',
    ];

    protected $casts = [
        'tarif_at_usage' => 'decimal:2',
        'jaspel_percentage_at_usage' => 'decimal:2',
        'master_data_snapshot' => 'array',
    ];

    public function jenisTindakan(): BelongsTo
    {
        return $this->belongsTo(JenisTindakan::class);
    }

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function getFormattedTarifAttribute(): string
    {
        return 'Rp ' . number_format($this->tarif_at_usage, 0, ',', '.');
    }

    public static function logUsage(JenisTindakan $jenisTindakan, Tindakan $tindakan, User $user): self
    {
        return static::create([
            'jenis_tindakan_id' => $jenisTindakan->id,
            'tindakan_id' => $tindakan->id,
            'used_by' => $user->id,
            'tarif_at_usage' => $jenisTindakan->tarif,
            'jaspel_percentage_at_usage' => $jenisTindakan->persentase_jaspel,
            'master_data_snapshot' => $jenisTindakan->toArray(),
        ]);
    }
}