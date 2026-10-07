<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualTransactionDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id',
        'entry_mode',
        'entered_litres',
        'entered_amount_minor',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entered_litres' => 'decimal:3',
            'entered_amount_minor' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class
        );
    }
}