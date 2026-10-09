<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $basicSalary = fake()->numberBetween(5000000, 15000000);
        $totalAllowance = fake()->numberBetween(500000, 3000000);
        $totalDeduction = fake()->numberBetween(200000, 1500000);
        $netSalary = $basicSalary + $totalAllowance - $totalDeduction;

        return [
            'employee_id' => Employee::factory(),
            'period_month' => fake()->numberBetween(1, 12),
            'period_year' => (int) date('Y'),
            'basic_salary' => $basicSalary,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'status' => 'draft',
            'generated_at' => null,
        ];
    }

    public function generated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'generated',
            'generated_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'generated_at' => now()->subDays(2),
        ]);
    }
}
