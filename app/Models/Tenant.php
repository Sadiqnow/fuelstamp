<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_code',
        'name',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}