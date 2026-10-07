<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'nozzle_id',
        'captured_by_user_id',
        'reading_type',
        'meter_litres',
        'captured_at',
        'evidence_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'meter_litres' => 'decimal:3',
            'captured_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(
            Station::class
        );
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(
            Shift::class
        );
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(
            Nozzle::class
        );
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'captured_by_user_id'
        );
    }
}