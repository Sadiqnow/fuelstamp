<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletHold extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'wallet_account_id',
        'asset_type',
        'fuel_product_id',
        'amount_minor',
        'volume_litres',
        'status',
        'reference_type',
        'reference_id',
        'expires_at',
        'consumed_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'volume_litres' => 'decimal:3',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(
            BuyerWallet::class
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            WalletAccount::class,
            'wallet_account_id'
        );
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(
            FuelProduct::class
        );
    }
}