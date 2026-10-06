<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pump extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'pump_code',
        'status',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }
}