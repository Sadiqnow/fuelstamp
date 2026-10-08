<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'asset_type',
        'fuel_product_id',
        'balance_minor',
        'balance_litres',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance_minor' => 'integer',
            'balance_litres' => 'decimal:3',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(
            BuyerWallet::class,
            'wallet_id'
        );
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(
            FuelProduct::class
        );
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(
            WalletLedgerEntry::class
        );
    }
}