<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SharedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'category',
        'description',
        'mime_type',
        'size',
        'storage_path',
        'owner_name',
        'uploaded_by',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shared_file_user')
            ->withPivot('shared_at')
            ->withTimestamps();
    }
}
