<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_code',
        'name',
        'phone',
        'email',
        'password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roleAssignments()
            ->whereNull('effective_to')
            ->whereHas('role', function ($query) use ($role) {
                $query->where('code', $role);
            })
            ->exists();
    }
}