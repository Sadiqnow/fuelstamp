<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tank extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'tank_code',
        'fuel_product_id',
        'capacity_litres',
        'low_level_threshold_litres',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity_litres' => 'decimal:3',
            'low_level_threshold_litres' => 'decimal:3',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }
}