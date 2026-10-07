<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'attendant_user_id',
        'nozzle_id',
        'fuel_product_id',
        'lane_type',
        'transaction_code',
        'volume_litres',
        'unit_price_minor',
        'amount_minor',
        'status',
        'idempotency_key',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'volume_litres' => 'decimal:3',
            'unit_price_minor' => 'integer',
            'amount_minor' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(
            Shift::class
        );
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(
            Station::class
        );
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'attendant_user_id'
        );
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(
            Nozzle::class
        );
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(
            FuelProduct::class
        );
    }

    public function manualDetail(): HasOne
    {
        return $this->hasOne(
            ManualTransactionDetail::class
        );
    }

    public function payment(): HasOne
    {
        return $this->hasOne(
            TransactionPayment::class
        );
    }
}