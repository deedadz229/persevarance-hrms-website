<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create([
            'department_id' => $department->id,
        ]);

        return [
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'department_id' => $department->id,
            'position_id' => $position->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'join_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'employment_type' => fake()->randomElement(['internship', 'contract', 'permanent']),
            'status' => 'active',
            'special_status' => null,
            'resigned_at' => null,
        ];
    }

    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resigned',
            'resigned_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
