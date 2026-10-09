<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'latitude',
        'longitude',
        'location_status',
        'face_status',
        'wifi_verified',
        'wifi_ssid',
        'ip_address',
        'photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'wifi_verified' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getComputedStatusAttribute(): string
    {
        if (strtolower($this->location_status ?? '') === 'wfh' || str_contains(strtolower($this->notes ?? ''), 'wfh')) {
            return 'wfh';
        }

        if ($this->check_in) {
            $checkInTime = $this->check_in->format('H:i:s');
            if ($checkInTime > '08:30:00' || str_contains(strtolower($this->notes ?? ''), 'telat') || strtolower($this->location_status ?? '') === 'late') {
                return 'telat';
            }

            return 'hadir';
        }

        return 'absen';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'wfh' => 'WFH',
            'telat' => 'Telat',
            'hadir' => 'Hadir',
            'absen' => 'Absen',
            default => 'Hadir',
        };
    }
}
