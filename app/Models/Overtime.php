<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'overtime_date',
        'start_time',
        'end_time',
        'duration',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'overtime_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'duration' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public static function calculateDuration(string $startTime, string $endTime): float
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $minutes = $start->diffInMinutes($end);

        return round($minutes / 60, 2);
    }

    public function getDurationHoursAttribute(): float
    {
        if ($this->duration !== null) {
            return (float) $this->duration;
        }

        if ($this->start_time && $this->end_time) {
            $startStr = is_string($this->start_time) ? $this->start_time : $this->start_time->format('H:i');
            $endStr = is_string($this->end_time) ? $this->end_time : $this->end_time->format('H:i');

            return self::calculateDuration($startStr, $endStr);
        }

        return 0.0;
    }

    public function getDurationLabelAttribute(): string
    {
        $hours = $this->duration_hours;

        return $hours == (int) $hours ? ((int) $hours).' Jam' : number_format($hours, 1).' Jam';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'badge-pending',
            'approved' => 'badge-approved',
            'rejected' => 'badge-rejected',
            default => 'badge-pending',
        };
    }
}
