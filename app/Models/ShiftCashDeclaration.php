<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCashDeclaration extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'declared_by_user_id',
        'declared_cash_minor',
        'declared_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'declared_cash_minor' => 'integer',
            'declared_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'declared_by_user_id'
        );
    }
}