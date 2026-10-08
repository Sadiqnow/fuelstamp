<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationNozzleLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'reconciliation_report_id',
        'nozzle_id',
        'opening_meter_litres',
        'closing_meter_litres',
        'meter_movement_litres',
        'ledger_litres',
        'variance_litres',
    ];

    protected function casts(): array
    {
        return [
            'opening_meter_litres' => 'decimal:3',
            'closing_meter_litres' => 'decimal:3',
            'meter_movement_litres' => 'decimal:3',
            'ledger_litres' => 'decimal:3',
            'variance_litres' => 'decimal:3',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(
            ReconciliationReport::class,
            'reconciliation_report_id'
        );
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }
}