<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $appends = [
        'label',
    ];

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions'
        );
    }

    public function getLabelAttribute(): string
    {
        return match ($this->name) {
            'super_admin' => 'Super Admin',
            'manager_hrd' => 'Management',
            'taiwan_account' => 'Taiwan Account',
            default => $this->name,
        };
    }
}
