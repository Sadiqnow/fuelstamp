<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'station_code',
        'name',
        'address',
        'latitude',
        'longitude',
        'status',
        'license_expiry',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'license_expiry' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pumps(): HasMany
    {
        return $this->hasMany(Pump::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(FuelPriceSchedule::class);
    }

    public function shiftTemplates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(StationStaff::class);
    }
}