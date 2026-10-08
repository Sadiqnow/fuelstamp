<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerWallet extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'wallet_code',
        'status',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(
            WalletAccount::class,
            'wallet_id'
        );
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(
            WalletLedgerEntry::class,
            'wallet_id'
        );
    }
}