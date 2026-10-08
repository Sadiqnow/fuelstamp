<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAuditPackage extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'station_id',
        'shift_id',
        'reconciliation_report_id',
        'package_hash',
        'package_payload',
        'sealed_by_user_id',
        'sealed_at',
    ];

    protected function casts(): array
    {
        return [
            'package_payload' => 'array',
            'sealed_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(
            ReconciliationReport::class,
            'reconciliation_report_id'
        );
    }

    public function sealedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sealed_by_user_id'
        );
    }
}