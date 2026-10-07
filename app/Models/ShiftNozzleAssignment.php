<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftNozzleAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'nozzle_id',
        'attendant_user_id',
        'custody_status',
        'assigned_at',
        'accepted_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'released_at' => 'datetime',
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

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'attendant_user_id'
        );
    }
}