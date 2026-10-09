<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'type',
        'name',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function scopeAllowances(Builder $query): Builder
    {
        return $query->where('type', 'allowance');
    }

    public function scopeDeductions(Builder $query): Builder
    {
        return $query->where('type', 'deduction');
    }

    public function scopeBpjs(Builder $query): Builder
    {
        return $query->where('type', 'deduction')->where('name', 'like', '%BPJS%');
    }

    public function scopePph21(Builder $query): Builder
    {
        return $query->where('type', 'deduction')->where('name', 'like', '%PPh21%');
    }
}
