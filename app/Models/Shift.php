<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_template_id',
        'manager_user_id',
        'shift_code',
        'status',
        'scheduled_start_at',
        'opened_at',
        'activated_at',
        'closing_started_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'opened_at' => 'datetime',
            'activated_at' => 'datetime',
            'closing_started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(
            ShiftTemplate::class,
            'shift_template_id'
        );
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'manager_user_id'
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            ShiftAssignment::class
        );
    }

    public function nozzleAssignments(): HasMany
    {
        return $this->hasMany(
            ShiftNozzleAssignment::class
        );
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(
            MeterReading::class
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            Transaction::class
        );
    }
}