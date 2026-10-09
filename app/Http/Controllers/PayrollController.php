<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanViewPayroll($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $departments = Department::orderBy('name')->get();
        $months = Payroll::getMonthNames();

        // Calculate summary cards directly from the database
        $summaryQuery = Payroll::query();
        if ($month) {
            $summaryQuery->where('period_month', $month);
        }
        if ($year) {
            $summaryQuery->where('period_year', $year);
        }

        $totalPayrollCount = (clone $summaryQuery)->count();
        $totalNetSalary = (float) (clone $summaryQuery)->sum('net_salary');

        // BPJS & PPh21 calculated from payroll_details connected to matching payrolls
        $payrollIds = (clone $summaryQuery)->pluck('id');

        $totalBpjs = (float) PayrollDetail::query()
            ->whereIn('payroll_id', $payrollIds)
            ->where('type', 'deduction')
            ->where('name', 'like', '%BPJS%')
            ->sum('amount');

        $totalPph21 = (float) PayrollDetail::query()
            ->whereIn('payroll_id', $payrollIds)
            ->where('type', 'deduction')
            ->where('name', 'like', '%PPh21%')
            ->sum('amount');

        // Filter payroll list
        $query = Payroll::query()
            ->with(['employee.department', 'employee.position', 'details'])
            ->latest('id');

        if ($month) {
            $query->where('period_month', $month);
        }

        if ($year) {
            $query->where('period_year', $year);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['draft', 'generated', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $payrolls = $query->paginate(15)->withQueryString();

        return view('payrolls.index', [
            'payrolls' => $payrolls,
            'departments' => $departments,
            'months' => $months,
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'summary' => [
                'total_count' => $totalPayrollCount,
                'total_net_salary' => $totalNetSalary,
                'total_bpjs' => $totalBpjs,
                'total_pph21' => $totalPph21,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureCanManagePayroll($request);

        $employees = Employee::query()
            ->where('status', 'active')
            ->with('department')
            ->orderBy('name')
            ->get();

        $months = Payroll::getMonthNames();
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');

        return view('payrolls.create', [
            'employees' => $employees,
            'months' => $months,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManagePayroll($request);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*.name' => ['nullable', 'string', 'max:100'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
            'allowances.*.description' => ['nullable', 'string', 'max:255'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.name' => ['nullable', 'string', 'max:100'],
            'deductions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deductions.*.description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,generated'],
        ]);

        $payroll = $this->payrollService->processPayroll(
            $validated,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Data payroll berhasil diproses dan disimpan.');
    }

    public function show(Request $request, Payroll $payroll): View
    {
        $this->ensureCanViewPayroll($request);

        $payroll->load(['employee.department', 'employee.position', 'details']);

        return view('payrolls.show', [
            'payroll' => $payroll,
        ]);
    }

    public function slip(Request $request, Payroll $payroll): View
    {
        $this->ensureCanViewPayroll($request);

        $payroll->load(['employee.department', 'employee.position', 'details']);

        return view('payrolls.slip', [
            'payroll' => $payroll,
        ]);
    }

    public function departmentSummary(Request $request): View
    {
        $this->ensureCanViewPayroll($request);

        $month = (int) ($request->query('month') ?: date('n'));
        $year = (int) ($request->query('year') ?: date('Y'));

        $months = Payroll::getMonthNames();
        $summary = $this->payrollService->getDepartmentSummary($month, $year);

        return view('payrolls.department_summary', [
            'summary' => $summary,
            'months' => $months,
            'currentMonth' => $month,
            'currentYear' => $year,
        ]);
    }

    public function evaluation(Request $request): View
    {
        $this->ensureCanViewPayroll($request);

        $month = (int) ($request->query('month') ?: date('n'));
        $year = (int) ($request->query('year') ?: date('Y'));
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;

        $departments = Department::query()->orderBy('name')->get();
        $months = Payroll::getMonthNames();
        $payrolls = $this->payrollService->getEvaluationData($month, $year, $departmentId);

        return view('payrolls.evaluation', [
            'payrolls' => $payrolls,
            'departments' => $departments,
            'months' => $months,
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
        ]);
    }

    public function correction(Request $request, Payroll $payroll): View
    {
        $this->ensureCanManagePayroll($request);

        $payroll->load(['employee.department', 'employee.position', 'details']);

        return view('payrolls.correction', [
            'payroll' => $payroll,
        ]);
    }

    public function updateCorrection(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->ensureCanManagePayroll($request);

        $validated = $request->validate([
            'correction_reason' => ['required', 'string', 'min:3', 'max:500'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*.name' => ['nullable', 'string', 'max:100'],
            'allowances.*.amount' => ['nullable', 'numeric', 'min:0'],
            'allowances.*.description' => ['nullable', 'string', 'max:255'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.name' => ['nullable', 'string', 'max:100'],
            'deductions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deductions.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payrollService->correctPayroll(
            $payroll,
            $validated,
            $validated['correction_reason'],
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil dikoreksi dan net salary telah dihitung ulang.');
    }

    public function generate(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->ensureCanManagePayroll($request);

        $this->payrollService->generatePayroll(
            $payroll,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->back()
            ->with('success', 'Status payroll berhasil diperbarui menjadi Dihasilkan (Generated).');
    }

    private function ensureCanViewPayroll(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $canView = $user->role?->name === 'taiwan_account'
            || $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'manage_payroll') ?? false);

        if (! $canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data payroll.');
        }
    }

    private function ensureCanManagePayroll(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only.');
        }

        $canManage = $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'manage_payroll') ?? false);

        if (! $canManage) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola data payroll.');
        }
    }
}
