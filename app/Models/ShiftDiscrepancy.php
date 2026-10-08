<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftDiscrepancy extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'reconciliation_report_id',
        'nozzle_id',
        'discrepancy_type',
        'status',
        'variance_litres',
        'variance_minor',
        'description',
        'resolution_notes',
        'created_by_user_id',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'variance_litres' => 'decimal:3',
            'variance_minor' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(
            ReconciliationReport::class,
            'reconciliation_report_id'
        );
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by_user_id'
        );
    }
}