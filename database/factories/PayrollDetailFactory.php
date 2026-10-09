<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollDetail>
 */
class PayrollDetailFactory extends Factory
{
    protected $model = PayrollDetail::class;

    public function definition(): array
    {
        return [
            'payroll_id' => Payroll::factory(),
            'type' => 'allowance',
            'name' => 'Tunjangan Operasional',
            'amount' => 500000,
            'description' => 'Tunjangan operasional bulanan',
        ];
    }

    public function bpjs(float $amount = 200000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'name' => 'BPJS Kesehatan & Ketenagakerjaan',
            'amount' => $amount,
            'description' => 'Iuran BPJS Karyawan',
        ]);
    }

    public function pph21(float $amount = 150000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'name' => 'PPh21',
            'amount' => $amount,
            'description' => 'Potongan PPh Pasal 21',
        ]);
    }

    public function allowance(string $name = 'Tunjangan Makan & Transport', float $amount = 750000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'allowance',
            'name' => $name,
            'amount' => $amount,
            'description' => 'Tunjangan bulanan',
        ]);
    }

    public function deduction(string $name = 'Potongan Lain-lain', float $amount = 100000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'name' => $name,
            'amount' => $amount,
            'description' => 'Potongan periode berjalan',
        ]);
    }
}
