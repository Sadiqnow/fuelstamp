<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nozzle extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'pump_id',
        'nozzle_code',
        'fuel_product_id',
        'status',
    ];

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class);
    }
}