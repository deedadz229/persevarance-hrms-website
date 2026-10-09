<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    /**
     * Calculate net salary and totals from basic salary, allowances, and deductions.
     */
    public function calculateNetSalary(float $basicSalary, array $allowances = [], array $deductions = []): array
    {
        $totalAllowance = 0.0;
        foreach ($allowances as $item) {
            $totalAllowance += (float) ($item['amount'] ?? 0);
        }

        $totalDeduction = 0.0;
        foreach ($deductions as $item) {
            $totalDeduction += (float) ($item['amount'] ?? 0);
        }

        $netSalary = $basicSalary + $totalAllowance - $totalDeduction;

        return [
            'basic_salary' => $basicSalary,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
        ];
    }

    /**
     * Process / Create a new payroll record in a database transaction.
     */
    public function processPayroll(array $payload, User $actor, ?string $ip = null, ?string $userAgent = null): Payroll
    {
        return DB::transaction(function () use ($payload, $actor, $ip, $userAgent) {
            $employeeId = (int) $payload['employee_id'];
            $month = (int) $payload['period_month'];
            $year = (int) $payload['period_year'];

            // Guard against duplicate payroll for the same employee and period
            $existing = Payroll::query()
                ->where('employee_id', $employeeId)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Data payroll untuk karyawan ini pada periode '.$month.'/'.$year.' sudah pernah dibuat.',
                ]);
            }

            $basicSalary = (float) $payload['basic_salary'];
            $allowances = $payload['allowances'] ?? [];
            $deductions = $payload['deductions'] ?? [];

            $calculation = $this->calculateNetSalary($basicSalary, $allowances, $deductions);
            $status = $payload['status'] ?? 'draft';

            $payroll = Payroll::create([
                'employee_id' => $employeeId,
                'period_month' => $month,
                'period_year' => $year,
                'basic_salary' => $calculation['basic_salary'],
                'total_allowance' => $calculation['total_allowance'],
                'total_deduction' => $calculation['total_deduction'],
                'net_salary' => $calculation['net_salary'],
                'status' => $status,
                'generated_at' => $status === 'generated' ? now() : null,
            ]);

            // Save allowance details
            foreach ($allowances as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                if ($amount > 0 && ! empty($item['name'])) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'allowance',
                        'name' => trim($item['name']),
                        'amount' => $amount,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            // Save deduction details (including BPJS, PPh21, etc.)
            foreach ($deductions as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                if ($amount > 0 && ! empty($item['name'])) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'deduction',
                        'name' => trim($item['name']),
                        'amount' => $amount,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            // Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_created',
                'entity_type' => Payroll::class,
                'entity_id' => $payroll->id,
                'old_values' => null,
                'new_values' => [
                    'employee_id' => $payroll->employee_id,
                    'period' => $payroll->period_label,
                    'basic_salary' => $payroll->basic_salary,
                    'total_allowance' => $payroll->total_allowance,
                    'total_deduction' => $payroll->total_deduction,
                    'net_salary' => $payroll->net_salary,
                    'status' => $payroll->status,
                ],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            return $payroll->load(['employee', 'details']);
        });
    }

    /**
     * Perform payroll correction with mandatory reason, recalculation, and audit log.
     */
    public function correctPayroll(Payroll $payroll, array $payload, string $reason, User $actor, ?string $ip = null, ?string $userAgent = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $payload, $reason, $actor, $ip, $userAgent) {
            $oldValues = [
                'basic_salary' => (float) $payroll->basic_salary,
                'total_allowance' => (float) $payroll->total_allowance,
                'total_deduction' => (float) $payroll->total_deduction,
                'net_salary' => (float) $payroll->net_salary,
                'details' => $payroll->details()->get(['type', 'name', 'amount'])->toArray(),
            ];

            $basicSalary = isset($payload['basic_salary']) ? (float) $payload['basic_salary'] : (float) $payroll->basic_salary;
            $allowances = $payload['allowances'] ?? [];
            $deductions = $payload['deductions'] ?? [];

            $calculation = $this->calculateNetSalary($basicSalary, $allowances, $deductions);

            // Replace existing details with updated items
            $payroll->details()->delete();

            foreach ($allowances as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                if ($amount > 0 && ! empty($item['name'])) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'allowance',
                        'name' => trim($item['name']),
                        'amount' => $amount,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            foreach ($deductions as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                if ($amount > 0 && ! empty($item['name'])) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'type' => 'deduction',
                        'name' => trim($item['name']),
                        'amount' => $amount,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }

            $payroll->update([
                'basic_salary' => $calculation['basic_salary'],
                'total_allowance' => $calculation['total_allowance'],
                'total_deduction' => $calculation['total_deduction'],
                'net_salary' => $calculation['net_salary'],
            ]);

            $payroll->refresh();

            $newValues = [
                'basic_salary' => (float) $payroll->basic_salary,
                'total_allowance' => (float) $payroll->total_allowance,
                'total_deduction' => (float) $payroll->total_deduction,
                'net_salary' => (float) $payroll->net_salary,
                'correction_reason' => $reason,
                'details' => $payroll->details()->get(['type', 'name', 'amount'])->toArray(),
            ];

            // Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_corrected',
                'entity_type' => Payroll::class,
                'entity_id' => $payroll->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            return $payroll->load(['employee', 'details']);
        });
    }

    /**
     * Transition a draft payroll to generated status.
     */
    public function generatePayroll(Payroll $payroll, User $actor, ?string $ip = null, ?string $userAgent = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $actor, $ip, $userAgent) {
            $oldStatus = $payroll->status;

            $payroll->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'payroll_generated',
                'entity_type' => Payroll::class,
                'entity_id' => $payroll->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'generated', 'generated_at' => now()->toDateTimeString()],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            return $payroll;
        });
    }

    /**
     * Efficient SQL aggregation per department for a specific period.
     */
    public function getDepartmentSummary(int $month, int $year): Collection
    {
        return Department::query()
            ->leftJoin('employees', 'employees.department_id', '=', 'departments.id')
            ->leftJoin('payrolls', function ($join) use ($month, $year) {
                $join->on('payrolls.employee_id', '=', 'employees.id')
                    ->where('payrolls.period_month', '=', $month)
                    ->where('payrolls.period_year', '=', $year);
            })
            ->select([
                'departments.id',
                'departments.name',
                DB::raw('COUNT(DISTINCT payrolls.employee_id) as total_employees'),
                DB::raw('COALESCE(SUM(payrolls.basic_salary), 0) as total_basic_salary'),
                DB::raw('COALESCE(SUM(payrolls.total_allowance), 0) as total_allowance'),
                DB::raw('COALESCE(SUM(payrolls.total_deduction), 0) as total_deduction'),
                DB::raw('COALESCE(SUM(payrolls.net_salary), 0) as total_net_salary'),
            ])
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('departments.name')
            ->get();
    }

    /**
     * Evaluation data for pre-finalization review.
     */
    public function getEvaluationData(int $month, int $year, ?int $departmentId = null): Collection
    {
        $query = Payroll::query()
            ->with(['employee.department', 'employee.position', 'details'])
            ->where('period_month', $month)
            ->where('period_year', $year);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $payrolls = $query->get();

        // Attach attendance days and approved overtime hours as non-destructive reference metrics
        foreach ($payrolls as $payroll) {
            $employeeId = $payroll->employee_id;

            $attendanceDays = Attendance::query()
                ->where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->whereIn('status', ['present', 'late'])
                ->count();

            $overtimeHours = (float) Overtime::query()
                ->where('employee_id', $employeeId)
                ->whereYear('overtime_date', $year)
                ->whereMonth('overtime_date', $month)
                ->where('status', 'approved')
                ->sum('duration');

            $payroll->reference_attendance_days = $attendanceDays;
            $payroll->reference_overtime_hours = $overtimeHours;
        }

        return $payrolls;
    }
}
