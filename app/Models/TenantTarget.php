<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'year',
        'month',
        'target_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'target_amount' => 'decimal:2',
    ];

    public const MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        $monthName = self::MONTH_NAMES[$this->month] ?? (string) $this->month;
        return "{$monthName} {$this->year}";
    }
}
