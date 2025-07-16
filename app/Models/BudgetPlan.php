<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'month',
        'department',
        'target_pendapatan',
        'target_pengeluaran',
        'target_net_profit',
        'category',
        'description',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'target_pendapatan' => 'decimal:2',
        'target_pengeluaran' => 'decimal:2',
        'target_net_profit' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Accessors
    public function getTargetPendapatanFormattedAttribute()
    {
        return 'Rp ' . number_format($this->target_pendapatan, 0, ',', '.');
    }

    public function getTargetPengeluaranFormattedAttribute()
    {
        return 'Rp ' . number_format($this->target_pengeluaran, 0, ',', '.');
    }

    public function getTargetNetProfitFormattedAttribute()
    {
        return 'Rp ' . number_format($this->target_net_profit, 0, ',', '.');
    }

    // Static methods
    public static function getCurrentBudget()
    {
        $now = now();
        return self::forMonth($now->year, $now->month)
            ->approved()
            ->first();
    }

    public static function getBudgetForMonth($year, $month)
    {
        return self::forMonth($year, $month)
            ->approved()
            ->first();
    }

    public static function getYearlyBudget($year)
    {
        return self::forYear($year)
            ->approved()
            ->get();
    }

    // Helper methods
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function calculateVariance($actualPendapatan, $actualPengeluaran)
    {
        return [
            'pendapatan_variance' => $actualPendapatan - $this->target_pendapatan,
            'pengeluaran_variance' => $actualPengeluaran - $this->target_pengeluaran,
            'net_variance' => ($actualPendapatan - $actualPengeluaran) - $this->target_net_profit,
        ];
    }

    public function getPerformancePercentage($actualPendapatan, $actualPengeluaran)
    {
        return [
            'pendapatan_percentage' => $this->target_pendapatan > 0 ? ($actualPendapatan / $this->target_pendapatan) * 100 : 0,
            'pengeluaran_percentage' => $this->target_pengeluaran > 0 ? ($actualPengeluaran / $this->target_pengeluaran) * 100 : 0,
        ];
    }
}