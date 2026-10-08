<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'wallet_account_id',
        'direction',
        'asset_type',
        'fuel_product_id',
        'amount_minor',
        'volume_litres',
        'balance_after_minor',
        'balance_after_litres',
        'entry_type',
        'reference_type',
        'reference_id',
        'reference_code',
        'idempotency_key',
        'created_by_user_id',
        'notes',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'volume_litres' => 'decimal:3',
            'balance_after_minor' => 'integer',
            'balance_after_litres' => 'decimal:3',
            'posted_at' => 'datetime',
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