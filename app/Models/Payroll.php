<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_month',
        'period_year',
        'basic_salary',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'basic_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public static function getMonthNames(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(PayrollDetail::class)->where('type', 'allowance');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDetail::class)->where('type', 'deduction');
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = self::getMonthNames();
        $monthName = $months[$this->period_month] ?? 'Bulan '.$this->period_month;

        return "{$monthName} {$this->period_year}";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'generated' => 'Dihasilkan',
            'paid' => 'Dibayar',
            default => 'Draft',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'generated' => 'badge-generated',
            'paid' => 'badge-paid',
            default => 'badge-draft',
        };
    }
}
