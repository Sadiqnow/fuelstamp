<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'generated_by_user_id',
        'status',
        'ledger_litres',
        'meter_movement_litres',
        'volume_variance_litres',
        'total_revenue_minor',
        'expected_cash_minor',
        'declared_cash_minor',
        'cash_variance_minor',
        'generated_at',
        'reviewed_at',
        'reviewed_by_user_id',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'ledger_litres' => 'decimal:3',
            'meter_movement_litres' => 'decimal:3',
            'volume_variance_litres' => 'decimal:3',
            'total_revenue_minor' => 'integer',
            'expected_cash_minor' => 'integer',
            'declared_cash_minor' => 'integer',
            'cash_variance_minor' => 'integer',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function nozzleLines(): HasMany
    {
        return $this->hasMany(
            ReconciliationNozzleLine::class
        );
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(
            ShiftDiscrepancy::class
        );
    }
}