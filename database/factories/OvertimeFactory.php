<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Overtime>
 */
class OvertimeFactory extends Factory
{
    protected $model = Overtime::class;

    public function definition(): array
    {
        $date = now()->format('Y-m-d');

        return [
            'employee_id' => Employee::factory(),
            'overtime_date' => $date,
            'start_time' => '18:00',
            'end_time' => '21:00',
            'duration' => 3.00,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(?User $approver = null, string $reason = 'Alokasi jam lembur departemen telah mencapai batas bulanan'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
