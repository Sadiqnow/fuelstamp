<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'buyer_user_id',
        'wallet_id',
        'wallet_account_id',
        'wallet_hold_id',
        'station_id',
        'fuel_product_id',
        'asset_type',
        'authorization_mode',
        'authorized_amount_minor',
        'authorized_volume_litres',
        'code',
        'qr_token_hash',
        'pin_hash',
        'status',
        'failed_attempts',
        'max_attempts',
        'expires_at',
        'redeemed_at',
        'redeemed_transaction_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'idempotency_key',
    ];

    protected $hidden = [
        'qr_token_hash',
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'authorized_amount_minor' =>
                'integer',

            'authorized_volume_litres' =>
                'decimal:3',

            'failed_attempts' =>
                'integer',

            'max_attempts' =>
                'integer',

            'expires_at' =>
                'datetime',

            'redeemed_at' =>
                'datetime',

            'cancelled_at' =>
                'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'buyer_user_id'
        );
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(
            BuyerWallet::class
        );
    }

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(
            WalletAccount::class
        );
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(
            WalletHold::class,
            'wallet_hold_id'
        );
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(
            Station::class
        );
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(
            FuelProduct::class
        );
    }

    public function redeemedTransaction(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class,
            'redeemed_transaction_id'
        );
    }
}