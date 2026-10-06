<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPriceSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'fuel_product_id',
        'price_minor_per_litre',
        'effective_from',
        'effective_to',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price_minor_per_litre' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }
}