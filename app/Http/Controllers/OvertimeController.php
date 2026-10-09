<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanViewOvertime($request);

        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');
        $date = $request->query('date');

        $departments = Department::query()->orderBy('name')->get();

        // Calculate summary cards directly from SQLite
        $totalHours = (float) Overtime::query()
            ->where('status', 'approved')
            ->sum('duration');

        $summary = [
            'total_hours' => $totalHours,
            'approved' => Overtime::query()->where('status', 'approved')->count(),
            'pending' => Overtime::query()->where('status', 'pending')->count(),
            'total_records' => Overtime::query()->count(),
        ];

        $query = Overtime::query()
            ->with(['employee.department', 'employee.position', 'approver']);

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($date) {
            $query->whereDate('overtime_date', $date);
        }

        $overtimes = $query->latest('overtime_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('overtimes.index', [
            'overtimes' => $overtimes,
            'departments' => $departments,
            'summary' => $summary,
            'currentSearch' => $search,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentDate' => $date,
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureCanManageOvertime($request);

        $employees = Employee::query()
            ->where('status', 'active')
            ->with('department')
            ->orderBy('name')
            ->get();

        return view('overtimes.create', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageOvertime($request);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'overtime_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $duration = Overtime::calculateDuration($validated['start_time'], $validated['end_time']);

        $overtime = Overtime::create([
            'employee_id' => $validated['employee_id'],
            'overtime_date' => $validated['overtime_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration' => $duration,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'overtime_created',
            'entity_type' => Overtime::class,
            'entity_id' => $overtime->id,
            'old_values' => null,
            'new_values' => [
                'employee_id' => $overtime->employee_id,
                'overtime_date' => $overtime->overtime_date?->format('Y-m-d'),
                'duration' => $duration,
                'status' => 'pending',
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('overtimes.index')
            ->with('success', 'Pengajuan lembur berhasil dibuat.');
    }

    public function show(Request $request, Overtime $overtime): View
    {
        $this->ensureCanViewOvertime($request);

        $overtime->loadMissing([
            'employee.department',
            'employee.position',
            'approver',
        ]);

        return view('overtimes.show', [
            'overtime' => $overtime,
        ]);
    }

    public function approve(Request $request, Overtime $overtime): RedirectResponse
    {
        $this->ensureCanApproveOvertime($request);

        if ($overtime->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan lembur ini sudah diproses sebelumnya dan tidak dapat diubah lagi.');
        }

        $user = $request->user();
        $oldStatus = $overtime->status;

        $overtime->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'overtime_approved',
            'entity_type' => Overtime::class,
            'entity_id' => $overtime->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => [
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now()->toDateTimeString(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan lembur berhasil disetujui.');
    }

    public function reject(Request $request, Overtime $overtime): RedirectResponse
    {
        $this->ensureCanApproveOvertime($request);

        if ($overtime->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan lembur ini sudah diproses sebelumnya dan tidak dapat diubah lagi.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan lembur wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $user = $request->user();
        $oldStatus = $overtime->status;

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'overtime_rejected',
            'entity_type' => Overtime::class,
            'entity_id' => $overtime->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => [
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now()->toDateTimeString(),
                'rejection_reason' => $validated['rejection_reason'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan lembur telah ditolak.');
    }

    private function ensureCanViewOvertime(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $canView = $user->role?->name === 'taiwan_account'
            || $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'manage_overtime') ?? false);

        if (! $canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data lembur.');
        }
    }

    private function ensureCanManageOvertime(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only.');
        }

        $hasPermission = $user->role?->permissions->contains('name', 'manage_overtime');
        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki permission untuk mengelola data lembur.');
        }
    }

    private function ensureCanApproveOvertime(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only.');
        }

        $hasPermission = $user->role?->permissions->contains('name', 'approve_overtime');
        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki permission untuk menyetujui atau menolak lembur.');
        }
    }
}
