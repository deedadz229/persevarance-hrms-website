<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 10));
        $end = (clone $start)->addDays(fake()->numberBetween(0, 3));

        return [
            'employee_id' => Employee::factory(),
            'leave_type' => fake()->randomElement(['Cuti Tahunan', 'Izin Sakit', 'Izin Pribadi', 'Cuti Khusus']),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'sync_status' => 'pending',
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

    public function rejected(?User $approver = null, string $reason = 'Kuota operasional departemen tidak mencukupi pada periode tersebut'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
