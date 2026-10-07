<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'name',
        'duration_minutes',
        'break_minutes',
        'reconciliation_window_minutes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'break_minutes' => 'integer',
            'reconciliation_window_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}