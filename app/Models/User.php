<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'employee_id',
        'name',
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
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'approved_by'
        );
    }

    public function approvedOvertimes(): HasMany
    {
        return $this->hasMany(
            Overtime::class,
            'approved_by'
        );
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function sharedFiles(): BelongsToMany
    {
        return $this->belongsToMany(SharedFile::class, 'shared_file_user')
            ->withPivot('shared_at')
            ->withTimestamps();
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'uploaded_by');
    }
}
