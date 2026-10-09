<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'department_id',
        'position_id',
        'name',
        'email',
        'phone',
        'join_date',
        'employment_type',
        'status',
        'special_status',
        'resigned_at',
        'photo_path',
        'photo_version',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'resigned_at' => 'date',
            'photo_version' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            return Storage::url($this->photo_path);
        }

        return null;
    }

    public static function generateNextEmployeeCode(): string
    {
        $codes = static::query()->pluck('employee_code');

        if ($codes->isEmpty()) {
            return '1';
        }

        $maxNumeric = null;
        $maxPrefixed = null;
        $lastPrefix = '';
        $lastPadLength = 0;

        foreach ($codes as $code) {
            $codeStr = trim((string) $code);
            if (ctype_digit($codeStr)) {
                $num = (int) $codeStr;
                if ($maxNumeric === null || $num > $maxNumeric) {
                    $maxNumeric = $num;
                }
            } elseif (preg_match('/^([A-Za-z\-_]+)(\d+)$/', $codeStr, $matches)) {
                $num = (int) $matches[2];
                if ($maxPrefixed === null || $num > $maxPrefixed) {
                    $maxPrefixed = $num;
                    $lastPrefix = $matches[1];
                    $lastPadLength = strlen($matches[2]);
                }
            }
        }

        if ($maxNumeric !== null) {
            return (string) ($maxNumeric + 1);
        }

        if ($maxPrefixed !== null) {
            return $lastPrefix.str_pad((string) ($maxPrefixed + 1), $lastPadLength, '0', STR_PAD_LEFT);
        }

        return (string) ($codes->count() + 1);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function sharedFiles(): HasMany
    {
        return $this->hasMany(SharedFile::class);
    }
}
