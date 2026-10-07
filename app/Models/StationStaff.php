<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StationStaff extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'station_staff';

    protected $fillable = [
        'tenant_id',
        'station_id',
        'user_id',
        'staff_type',
        'status',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}