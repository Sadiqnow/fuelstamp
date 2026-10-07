<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id',
        'payment_method',
        'amount_minor',
        'payment_reference',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class
        );
    }
}